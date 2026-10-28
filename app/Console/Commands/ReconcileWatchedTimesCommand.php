<?php

namespace App\Console\Commands;

use App\Models\Certificate;
use App\Models\Tenant;
use App\Models\TrainingLog;
use App\Models\TrainingRewatchRequest;
use App\Models\UserProgress;
use App\Services\AuditLogger;
use App\Support\TenantManager;
use Illuminate\Console\Command;

class ReconcileWatchedTimesCommand extends Command
{
    protected $signature = 'training:reconcile-watched-times
                            {--dry-run : Apenas simula as alterações sem gravar no banco}
                            {--flag-rewatch : Cria solicitação formal de reassistir para casos de evasão/bypass}
                            {--tenant= : ID do tenant específico (opcional)}
                            {--force : Executa sem pedir confirmação interativa}';

    protected $description = 'Reconcilia tempos assistidos truncados por falhas técnicas de heartbeat e identifica sessões com tempo incompatível (NR-01 Anexo II)';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $flagRewatch = (bool) $this->option('flag-rewatch');
        $tenantId = $this->option('tenant');
        $force = (bool) $this->option('force');

        if (! $dryRun && ! $force) {
            if (! $this->confirm('Deseja realmente aplicar os ajustes de reconciliação no banco de dados?', true)) {
                $this->info('Operação cancelada pelo usuário.');

                return self::SUCCESS;
            }
        }

        $this->info($dryRun ? '=== MODO SIMULAÇÃO (DRY-RUN) ATIVADO ===' : '=== APLICANDO RECONCILIAÇÃO TÉCNICA ===');

        $manager = app(TenantManager::class);

        $categoriaA = []; // Falhas do sistema corrigíveis
        $categoriaB = []; // Casos de tempo incompatível (bypass)

        $processar = function () use (&$categoriaA, &$categoriaB, $dryRun, $flagRewatch) {
            $progressos = UserProgress::with(['user', 'training'])
                ->where('concluido', true)
                ->get();

            foreach ($progressos as $p) {
                $training = $p->training;
                $user = $p->user;

                if (! $training || ! $user) {
                    continue;
                }

                // Usuário de teste não entra na reconciliação de conformidade
                if ($user->isTestUser()) {
                    continue;
                }

                $duracao = max(1, $training->duracaoSegundos());
                $limiarMinimo = (int) round($duracao * 0.90);

                $cert = Certificate::where('user_id', $user->id)
                    ->where('training_id', $training->id)
                    ->where('valido', true)
                    ->first();

                $tempoProgresso = (int) $p->tempo_assistido;
                $tempoCert = $cert ? (int) $cert->tempo_assistido_segundos : $tempoProgresso;

                // Identifica se há anomalia de tempo truncado no progresso ou no certificado
                $precisaReconciliar = ($tempoProgresso < $limiarMinimo) || ($tempoCert < $limiarMinimo);

                if (! $precisaReconciliar) {
                    continue;
                }

                $deltaReal = ($p->data_conclusao && $p->data_inicio)
                    ? max(0, $p->data_conclusao->diffInSeconds($p->data_inicio))
                    : 0;

                // CLASSIFICAÇÃO TÉCNICA:
                // Categoria A: Usuário permaneceu conectado por período compatível com a aula (>= 85% da duração),
                // mas o heartbeat travou no teto do servidor (+10s) ou no throttling do mobile.
                if ($deltaReal >= (int) round($duracao * 0.85)) {
                    $categoriaA[] = [
                        'user_id' => $user->id,
                        'nome' => $user->nome,
                        'cpf' => $user->cpf,
                        'training_id' => $training->id,
                        'titulo' => $training->titulo,
                        'duracao' => $duracao,
                        'tempo_anterior' => $tempoProgresso,
                        'cert_anterior' => $tempoCert,
                        'delta_real' => $deltaReal,
                        'cert_id' => $cert?->id,
                        'progress_id' => $p->id,
                    ];

                    if (! $dryRun) {
                        $p->update([
                            'tempo_assistido' => $duracao,
                            'porcentagem_assistida' => 100,
                        ]);

                        if ($cert) {
                            $cert->update([
                                'tempo_assistido_segundos' => $duracao,
                                'porcentagem_assistida' => 100,
                            ]);
                        }

                        TrainingLog::registrar(
                            $training->id,
                            $user->id,
                            'reconciliacao_auditoria',
                            "Reconciliação técnica NR-01 Anexo II: tempo assistido ajustado de {$tempoProgresso}s para {$duracao}s com base na permanência real conectada de {$deltaReal}s."
                        );

                        try {
                            app(AuditLogger::class)->log('updated', [
                                'module' => 'user_progress',
                                'auditable_type' => UserProgress::class,
                                'auditable_id' => $p->id,
                                'description' => "Reconciliação de tempo assistido no treinamento #{$training->id} para o usuário #{$user->id}",
                            ]);
                        } catch (\Throwable $e) {
                            // AuditLogger nunca interrompe o comando
                        }
                    }
                } else {
                    // Categoria B: Sessão finalizada em tempo muito inferior à duração (impossibilidade física de ter assistido)
                    $categoriaB[] = [
                        'user_id' => $user->id,
                        'nome' => $user->nome,
                        'cpf' => $user->cpf,
                        'training_id' => $training->id,
                        'titulo' => $training->titulo,
                        'duracao' => $duracao,
                        'tempo_registrado' => $tempoProgresso,
                        'delta_real' => $deltaReal,
                        'cert_id' => $cert?->id,
                        'progress_id' => $p->id,
                    ];

                    if (! $dryRun && $flagRewatch) {
                        $jaExiste = TrainingRewatchRequest::where('user_id', $user->id)
                            ->where('training_id', $training->id)
                            ->whereIn('status', ['pendente', 'aprovado'])
                            ->exists();

                        if (! $jaExiste) {
                            TrainingRewatchRequest::create([
                                'user_id' => $user->id,
                                'training_id' => $training->id,
                                'status' => 'pendente',
                                'justificativa' => "Auditoria técnica NR-01 Anexo II: tempo de sessão de {$deltaReal}s incompatível com a duração de {$duracao}s do conteúdo. Necessário reassistir integralmente.",
                                'certificate_anterior_id' => $cert?->id,
                            ]);

                            TrainingLog::registrar(
                                $training->id,
                                $user->id,
                                'reassistir_solicitado_auditoria',
                                "Solicitação de reassistir gerada automaticamente pela auditoria (sessão de {$deltaReal}s incompatível com {$duracao}s)."
                            );
                        }
                    }
                }
            }
        };

        if ($tenantId) {
            $tenant = Tenant::findOrFail((int) $tenantId);
            $manager->set($tenant);
            $processar();
            $manager->clear();
        } else {
            $manager->runForEachTenant(function () use ($processar) {
                $processar();
            });
        }

        // Tabela Categoria A
        $this->newLine();
        $this->info('========================================================================================');
        $this->info('CATEGORIA A: Registros Legítimos Afetados por Truncamento Técnico (Reconciliados)');
        $this->info('========================================================================================');
        if (empty($categoriaA)) {
            $this->line('Nenhum registro da Categoria A encontrado.');
        } else {
            $linhasA = array_map(function ($r) {
                return [
                    $r['nome'],
                    $r['titulo'],
                    gmdate('H:i:s', $r['duracao']),
                    gmdate('H:i:s', $r['delta_real']),
                    gmdate('H:i:s', $r['tempo_anterior']),
                    $r['cert_id'] ? "Cert #{$r['cert_id']} (".gmdate('H:i:s', $r['cert_anterior']).')' : 'Sem cert',
                    '-> '.gmdate('H:i:s', $r['duracao']),
                ];
            }, $categoriaA);

            $this->table(
                ['Colaborador', 'Treinamento', 'Duração', 'Tempo Conectado', 'Tempo Anterior', 'Certificado Anterior', 'Ajustado Para'],
                $linhasA
            );
            $this->info('Total Categoria A: '.count($categoriaA).' registro(s).');
        }

        // Tabela Categoria B
        $this->newLine();
        $this->warn('========================================================================================');
        $this->warn('CATEGORIA B: Sessões com Tempo Incompatível / Evasão Detectada');
        $this->warn('========================================================================================');
        if (empty($categoriaB)) {
            $this->line('Nenhum registro da Categoria B encontrado.');
        } else {
            $linhasB = array_map(function ($r) use ($flagRewatch, $dryRun) {
                $statusAcao = 'Identificado para decisão';
                if ($flagRewatch) {
                    $statusAcao = $dryRun ? 'Criaria Reassistir' : 'Reassistir Gerado';
                }

                return [
                    $r['nome'],
                    $r['titulo'],
                    gmdate('H:i:s', $r['duracao']),
                    gmdate('H:i:s', $r['delta_real']),
                    gmdate('H:i:s', $r['tempo_registrado']),
                    $r['cert_id'] ? "Cert #{$r['cert_id']}" : 'Sem cert',
                    $statusAcao,
                ];
            }, $categoriaB);

            $this->table(
                ['Colaborador', 'Treinamento', 'Duração', 'Tempo Conectado', 'Tempo Registrado', 'Certificado', 'Ação'],
                $linhasB
            );
            $this->warn('Total Categoria B: '.count($categoriaB).' registro(s).');
        }

        $this->newLine();
        if ($dryRun) {
            $this->comment('Simulação concluída. Nenhuma alteração foi realizada no banco de dados.');
            $this->comment('Para aplicar as correções, execute: php artisan training:reconcile-watched-times');
            if (! empty($categoriaB) && ! $flagRewatch) {
                $this->comment('Dica: use --flag-rewatch para gerar automaticamente os pedidos de reassistir da Categoria B.');
            }
        } else {
            $this->info('Reconciliação técnica concluída com sucesso!');
        }

        return self::SUCCESS;
    }
}
