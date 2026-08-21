<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\Training;
use App\Models\TrainingLog;
use App\Models\User;
use App\Models\UserProgress;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class TrainingAnalyzer
{
    /**
     * Retorna métricas agregadas para um treinamento (ou para todos quando null)
     * Usa Certificate como fonte de verdade para conclusões reais
     */
    public function analyze(?int $trainingId = null): array
    {
        $trainingFilter = $trainingId ? Training::find($trainingId) : null;

        // Obter usuários ativos elegíveis (mesma lógica do dashboard)
        if ($trainingFilter) {
            $usuariosAtivosQuery = $this->getElegibleQuery($trainingFilter);
        } else {
            $usuariosAtivosQuery = User::kpiEligible();
        }
        $totalUsuariosAtivos = $usuariosAtivosQuery->count();

        // Obter certificados (fonte de verdade para conclusões)
        $certQuery = Certificate::where('valido', true);
        if ($trainingId) {
            $certQuery->where('training_id', $trainingId);
        }
        if ($trainingFilter) {
            $certQuery->whereHas('user', function ($q) use ($trainingFilter) {
                $elegiveis = $this->getElegibleQuery($trainingFilter)->pluck('id');
                $q->whereIn('id', $elegiveis);
            });
        }

        $certificates = $certQuery->get();

        // Obter progressos para métricas complementares
        $progQuery = UserProgress::query();
        if ($trainingId) {
            $progQuery->where('training_id', $trainingId);
        }
        if ($trainingFilter) {
            $progQuery->whereHas('user', function ($q) use ($trainingFilter) {
                $elegiveis = $this->getElegibleQuery($trainingFilter)->pluck('id');
                $q->whereIn('id', $elegiveis);
            });
        }

        $progressos = $progQuery->get();

        $total = $progressos->count();
        $concluidos = $certificates->pluck('user_id')->unique()->count(); // usuários distintos com certificado válido
        $iniciados = $progressos->whereNotNull('data_inicio')->count();

        // Tempo médio assistido - usar Certificate quando disponível, senão UserProgress
        $times = [];
        if ($certificates->count() > 0) {
            $times = $certificates->map(function ($c) {
                return (int) ($c->tempo_assistido_segundos ?? 0);
            })->filter()->values();
        } else {
            $times = $progressos->map(function ($p) {
                return (int) ($p->tempo_assistido ?? 0);
            })->filter()->values();
        }
        $avgSeconds = $times->count() ? (int) ($times->sum() / $times->count()) : 0;

        // Dias médios para conclusão (entre data_inicio_assistencia e data_finalizacao_assistencia)
        // Usar Certificate como base, com dias de calendário
        $durations = $certificates->filter(function ($c) {
            return $c->data_inicio_assistencia && $c->data_finalizacao_assistencia;
        })->map(function ($c) {
            $start = strtotime($c->data_inicio_assistencia);
            $end = strtotime($c->data_finalizacao_assistencia);

            // Calcular dias de calendário: ceil para contar parciais como 1 dia
            return max(1, ceil(($end - $start) / 86400));
        });
        $avgDays = $durations->count() ? round($durations->avg(), 1) : null;

        // Usuários com certificados (participantes com sucesso)
        // Filtrar certificados removendo super_admin, usuário de teste e admin que não participa
        $certUserIds = $certificates->pluck('user_id')->unique()->values()->all();
        $certUsers = User::whereIn('id', $certUserIds)->get()->keyBy('id');

        $filteredCertUserIds = [];
        $vacationIncluded = 0;
        foreach ($certUserIds as $uid) {
            $u = $certUsers->get($uid);
            if (! $u) {
                continue;
            }
            // Excluir super_admin e usuários de teste
            if ($u->isSuperAdmin() || $u->isTestUser()) {
                continue;
            }
            // Excluir admin que não participa de treinamentos
            if ($u->role && $u->role->nome === 'admin' && ! $u->participa_treinamentos) {
                continue;
            }

            $filteredCertUserIds[] = $uid;

            // Se está em férias atualmente e gerou certificado, contaremos para o denominador efetivo
            if ($u->isOnVacation()) {
                $vacationIncluded++;
            }
        }

        $usuariosComCertificado = count($filteredCertUserIds);
        $concluidos = $usuariosComCertificado;

        // Denominador efetivo: usuários ativos elegíveis + aqueles em férias que participaram (evita duplicação)
        $usuariosAtivosEfectivos = $totalUsuariosAtivos + $vacationIncluded;

        // Métrica crítica: % de usuários ativos (efetivo) que completaram
        $percentualUsuariosAtivos = $usuariosAtivosEfectivos > 0
            ? round(($usuariosComCertificado / $usuariosAtivosEfectivos) * 100, 1)
            : 0;

        // compor resumo heurístico
        $metrics = [
            'total_progressos' => $total,
            'usuarios_ativos_total' => $totalUsuariosAtivos,
            'usuarios_ativos_total_effective' => $usuariosAtivosEfectivos,
            'usuarios_com_certificado' => $usuariosComCertificado,
            'percentual_usuarios_ativos' => $percentualUsuariosAtivos,
            'iniciados' => $iniciados,
            'concluidos' => $concluidos,
            'percent_concluidos' => $total ? round(($concluidos / $total) * 100, 1) : 0,
            'avg_time_seconds' => $avgSeconds,
            'avg_time_human' => $this->secondsToHms($avgSeconds),
            'avg_days_to_complete' => $avgDays,
        ];

        $metrics['human_summary'] = $this->composeSummary($metrics, $trainingId);

        return $metrics;
    }

    /**
     * Query de usuários elegíveis para um treinamento específico.
     * Mesma lógica do kpiEligible + eligibleForTrainingKpi, porém
     * inativos só são desconsiderados a partir da data de inativação.
     * Se inativaram APÓS a liberação do treinamento, ainda são elegíveis.
     *
     * @return Builder
     */
    protected function getElegibleQuery(Training $training)
    {
        $dataLiberacao = $training->data_liberacao ?? $training->data_publicacao ?? $training->created_at;

        $query = User::query()
            ->where(function ($q) use ($dataLiberacao) {
                $q->where('status', 'ativo')
                    ->orWhere(function ($q2) use ($dataLiberacao) {
                        if ($dataLiberacao) {
                            $q2->where('status', 'inativo')
                                ->whereNotNull('data_inativacao')
                                ->where('data_inativacao', '>=', $dataLiberacao);
                        }
                    });
            })
            ->where(function ($roleQuery) {
                $roleQuery->whereNull('role_id')
                    ->orWhereHas('role', function ($role) {
                        $role->where('nome', '<>', 'super_admin');
                    });
            })
            ->where('usuario_teste', false)
            ->where(function ($adminParticipationQuery) {
                $adminParticipationQuery->whereDoesntHave('role', function ($role) {
                    $role->where('nome', 'admin');
                })->orWhere('participa_treinamentos', true);
            })
            ->where(function ($vacationQuery) {
                $now = Carbon::now(config('app.timezone'));
                $vacationQuery->whereNull('ferias_inicio')
                    ->orWhereNull('ferias_fim')
                    ->orWhere(function ($notVacationQuery) use ($now) {
                        $notVacationQuery->whereDate('ferias_inicio', '>', $now->toDateString())
                            ->orWhereDate('ferias_fim', '<', $now->toDateString());
                    });
            });

        // eligibleForTrainingKpi: cadastro até o fim da semana da data do treinamento
        if ($dataLiberacao) {
            $maxUserCreatedAt = Carbon::parse($dataLiberacao, config('app.timezone'))
                ->endOfWeek(Carbon::SUNDAY)
                ->endOfDay();
            $query->where('created_at', '<=', $maxUserCreatedAt);
        }

        return $query;
    }

    /**
     * Relatório analítico completo e profissional para um treinamento específico.
     * Inclui metadados do treinamento, KPIs de cobertura, análise de tentativas
     * da avaliação (1ª/2ª tentativa, reassistiu conteúdo, aguardando) e a
     * tabela detalhada por usuário.
     */
    public function analyzeDetailed(Training $training): array
    {
        $trainingId = $training->id;

        // KPIs de cobertura (mesma fonte de verdade do analyze)
        $kpis = $this->analyze($trainingId);

        // ---- Usuários com progresso (todos que fizeram o treinamento, sem filtro de inativo) ----
        $todosProgressos = UserProgress::where('training_id', $trainingId)
            ->with('user')
            ->get();

        // Base elegível (só para KPIs e seção "Não Concluíram")
        $idsElegiveis = $this->getElegibleQuery($training)->pluck('id')->all();

        // Filtrar apenas: excluir super_admin, teste, admin sem participação, férias
        // NÃO exclui inativos — se fizeram o treinamento, aparecem no relatório
        $progressos = $todosProgressos->filter(function ($p) {
            $u = $p->user;
            if (! $u) {
                return false;
            }
            if ($u->isSuperAdmin() || $u->isTestUser()) {
                return false;
            }
            if ($u->role && $u->role->nome === 'admin' && ! $u->participa_treinamentos) {
                return false;
            }
            if ($u->isOnVacation()) {
                return false;
            }

            return true;
        })->values();

        // ---- Usuários que NÃO concluíram (apenas elegíveis) ----
        $certificadosValidos = Certificate::where('training_id', $trainingId)
            ->where('valido', true)
            ->pluck('user_id')
            ->unique()
            ->values()
            ->all();

        $naoConcluiram = [];
        foreach ($idsElegiveis as $uid) {
            if (in_array($uid, $certificadosValidos, true)) {
                continue; // concluiu
            }
            $u = User::find($uid);
            if (! $u) {
                continue;
            }
            // Usuários em férias não entram nessa análise
            if ($u->isOnVacation()) {
                continue;
            }
            $progUser = $todosProgressos->first(function ($p) use ($uid) {
                return $p->user_id === $uid;
            });
            if ($progUser) {
                $motivo = 'iniciou mas não finalizou';
                if ($progUser->data_inicio && ! $progUser->data_conclusao) {
                    $motivo = 'iniciou em '.$progUser->data_inicio->format('d/m/Y').' mas não finalizou';
                }
            } else {
                $motivo = 'não iniciou o treinamento';
            }

            $naoConcluiram[] = [
                'id' => $u->id,
                'nome' => $u->nome,
                'cpf' => $this->maskCpf($u->cpf),
                'tipo_usuario' => $u->tipo_usuario,
                'motivo' => $motivo,
            ];
        }

        // ---- Análise de tentativas via training_logs ----
        $logs = TrainingLog::where('training_id', $trainingId)
            ->whereIn('evento', ['avaliacao_submetida', 'avaliacao_reset'])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $logsPorUsuario = $logs->groupBy('user_id');
        $capturaInicio = TrainingLog::where('training_id', $trainingId)->min('created_at');
        $capturaInicio = $capturaInicio ? Carbon::parse($capturaInicio) : null;

        $avaliacao = $this->buildAssessmentSummary($logsPorUsuario, $progressos, $capturaInicio);

        // ---- Tabela detalhada por usuário ----
        $usuarios = $progressos->map(function ($p) use ($logsPorUsuario) {
            $u = $p->user;
            $logsUser = $logsPorUsuario->get($u->id);

            $nota = $p->avaliacao_nota;
            if ($nota === null && $logsUser) {
                foreach ($logsUser->where('evento', 'avaliacao_submetida')->reverse() as $log) {
                    $parsed = $this->parseSubmission($log->detalhe);
                    if ($parsed) {
                        $nota = $parsed['nota'];
                        break;
                    }
                }
            }

            return [
                'id' => $u->id,
                'nome' => $u->nome,
                'cpf' => $this->maskCpf($u->cpf),
                'setor' => $u->setor,
                'cargo' => $u->cargo,
                'empresa' => $u->empresa,
                'tipo_usuario' => $u->tipo_usuario,
                'data_inicio' => $p->data_inicio ? $p->data_inicio->format('d/m/Y') : null,
                'data_conclusao' => $p->data_conclusao ? $p->data_conclusao->format('d/m/Y') : null,
                'nota' => $nota,
                'tentativas' => $logsUser ? $logsUser->where('evento', 'avaliacao_submetida')->count() : null,
                'concluido' => (bool) $p->concluido,
                'porcentagem_assistida' => (int) $p->porcentagem_assistida,
                'tempo_assistido_human' => $this->secondsToHms((int) $p->tempo_assistido),
            ];
        })->sortByDesc('concluido')->sortBy('nome')->values()->all();

        return [
            'training' => $this->buildTrainingMetadata($training),
            'kpis' => $kpis,
            'avaliacao' => $avaliacao,
            'usuarios' => $usuarios,
            'nao_concluiram' => $naoConcluiram,
            'relatorio_gerado_em' => now()->format('d/m/Y H:i'),
        ];
    }

    /**
     * Compila as estatísticas de avaliação (tentativas) por usuário a partir dos logs.
     *
     * @param  Collection  $logsPorUsuario
     * @param  Collection  $progressos
     */
    protected function buildAssessmentSummary($logsPorUsuario, $progressos, ?Carbon $capturaInicio = null): array
    {
        $usersPorId = $progressos->map(function ($p) {
            return $p->user;
        })->keyBy('id');

        $grupos = [
            'aprovados_1a_tentativa' => [],
            'aprovados_2a_tentativa' => [],
            'reassistiram_conteudo' => [],
            'aguardando_2a_tentativa' => [],
        ];
        $todasNotas = [];
        $notasAprovacoes = [];
        $aprovadosSemRegistro = 0;
        $totalSubmissoes = 0;

        foreach ($logsPorUsuario as $uid => $userLogs) {
            $user = $usersPorId->get($uid);
            if (! $user) {
                continue;
            }

            $submissoes = $userLogs->where('evento', 'avaliacao_submetida')->values();
            $totalSubmissoes += $submissoes->count();

            $cycleFails = 0;
            $cycleSubmissions = 0;
            $rewatched = false;
            $approved = false;
            $approvalAttempt = null;
            $lastNota = null;
            $approvedAfterRewatch = false;

            foreach ($userLogs as $log) {
                if ($log->evento === 'avaliacao_reset') {
                    $rewatched = true;
                    $cycleFails = 0;
                    $cycleSubmissions = 0;

                    continue;
                }

                $parsed = $this->parseSubmission($log->detalhe);
                if (! $parsed) {
                    continue;
                }

                $cycleSubmissions++;
                $lastNota = $parsed['nota'];
                $todasNotas[] = $parsed['nota'];

                if ($parsed['aprovado']) {
                    $approved = true;
                    $approvalAttempt = $cycleSubmissions;
                    $notasAprovacoes[] = $parsed['nota'];
                    if ($rewatched) {
                        $approvedAfterRewatch = true;
                    }
                    $cycleFails = 0;
                    $cycleSubmissions = 0;
                } else {
                    $cycleFails++;
                    if ($cycleFails >= 2) {
                        // Duas falhas consecutivas sem evento de reset registrado (dados antigos)
                        $rewatched = true;
                        $cycleFails = 0;
                        $cycleSubmissions = 0;
                    }
                }
            }

            $linhaUsuario = $this->buildAttemptUserRow($user, $lastNota, $submissoes->count());

            if ($rewatched) {
                $linhaUsuario['status'] = $approved ? 'aprovado_apos_reassistir' : 'bloqueado_aguardando';
                $linhaUsuario['aprovado'] = $approved;
                $linhaUsuario['aprovado_apos_reassistir'] = $approvedAfterRewatch;
                $grupos['reassistiram_conteudo'][] = $linhaUsuario;
            } elseif ($approved && $approvalAttempt === 1) {
                $linhaUsuario['status'] = 'aprovado_1a_tentativa';
                $grupos['aprovados_1a_tentativa'][] = $linhaUsuario;
            } elseif ($approved && $approvalAttempt === 2) {
                $linhaUsuario['status'] = 'aprovado_2a_tentativa';
                $grupos['aprovados_2a_tentativa'][] = $linhaUsuario;
            } else {
                $linhaUsuario['status'] = 'aguardando_2a_tentativa';
                $grupos['aguardando_2a_tentativa'][] = $linhaUsuario;
            }
        }

        // Usuários aprovados sem qualquer registro de tentativa (concluíram antes da captura de logs).
        // Visualmente são incluídos no grupo de 1ª tentativa, com flag para distinção na interface.
        $idsComLogs = $logsPorUsuario->keys()->all();
        foreach ($usersPorId as $uid => $user) {
            if (in_array($uid, $idsComLogs, true)) {
                continue;
            }
            $progress = $progressos->first(function ($p) use ($uid) {
                return $p->user_id === $uid;
            });
            if ($progress && $progress->avaliacao_aprovada) {
                $aprovadosSemRegistro++;
                $linha = $this->buildAttemptUserRow($user, null, 0);
                $linha['tentativas'] = null;
                $linha['status'] = 'aprovado_1a_tentativa_historico';
                $linha['aprovado'] = true;
                $linha['sem_registro_tentativa'] = true;
                $grupos['aprovados_1a_tentativa'][] = $linha;
            }
        }

        $ordenarPorNome = function ($rows) {
            usort($rows, function ($a, $b) {
                return strcasecmp($a['nome'], $b['nome']);
            });

            return array_values($rows);
        };

        foreach ($grupos as $chave => $linhas) {
            $grupos[$chave] = [
                'total' => count($linhas),
                'usuarios' => $ordenarPorNome($linhas),
            ];
        }

        return [
            'captura_inicio' => $capturaInicio ? $capturaInicio->format('d/m/Y') : null,
            'total_submissoes' => $totalSubmissoes,
            'aprovados_1a_tentativa' => $grupos['aprovados_1a_tentativa'],
            'aprovados_2a_tentativa' => $grupos['aprovados_2a_tentativa'],
            'reassistiram_conteudo' => $grupos['reassistiram_conteudo'],
            'aguardando_2a_tentativa' => $grupos['aguardando_2a_tentativa'],
            'aprovados_sem_registro_tentativa' => $aprovadosSemRegistro,
            'nota_media_submissoes' => count($todasNotas) ? round(array_sum($todasNotas) / count($todasNotas), 1) : null,
            'nota_media_aprovacoes' => count($notasAprovacoes) ? round(array_sum($notasAprovacoes) / count($notasAprovacoes), 1) : null,
        ];
    }

    protected function buildAttemptUserRow(User $user, ?int $lastNota, int $tentativas): array
    {
        return [
            'id' => $user->id,
            'nome' => $user->nome,
            'cpf' => $this->maskCpf($user->cpf),
            'setor' => $user->setor,
            'cargo' => $user->cargo,
            'empresa' => $user->empresa,
            'tipo_usuario' => $user->tipo_usuario,
            'nota' => $lastNota,
            'tentativas' => $tentativas,
        ];
    }

    protected function buildTrainingMetadata(Training $training): array
    {
        $tiposPermitidos = is_array($training->tipo_usuario_permitido)
            ? $training->tipo_usuario_permitido
            : json_decode((string) $training->tipo_usuario_permitido, true);

        $labels = [
            'motorista' => 'Motoristas',
            'funcionario' => 'Funcionários',
            'terceirizado' => 'Terceirizados',
        ];
        $publicoLabel = null;
        if (is_array($tiposPermitidos)) {
            $publicoLabel = implode(', ', array_map(function ($t) use ($labels) {
                return $labels[$t] ?? ucfirst($t);
            }, $tiposPermitidos));
        } elseif ($tiposPermitidos === 'todos' || $training->tipo_usuario_permitido === null) {
            $publicoLabel = 'Todos os públicos';
        }

        return [
            'id' => $training->id,
            'titulo' => $training->titulo,
            'descricao' => $training->descricao,
            'conteudo_programatico' => $training->conteudo_programatico,
            'tipo' => $training->tipo,
            'tipo_label' => $training->tipo === 'dss' ? 'DSS' : 'Treinamento',
            'tipo_treinamento_label' => $training->getTipoTreinamentoLabelAttribute(),
            'carga_horaria' => $training->carga_horaria,
            'dias_validade' => $training->dias_validade,
            'nota_minima_aprovacao' => $training->nota_minima_aprovacao,
            'quantidade_questoes_prova' => $training->quantidade_questoes_prova,
            'total_questoes_banco' => $training->questions()->count(),
            'publico_alvo_label' => $publicoLabel,
            'tipo_video' => $training->tipo_video,
            'status' => $training->status,
            'status_label' => $training->status === 'ativo' ? 'Ativo' : 'Inativo',
            'obrigatorio' => (bool) $training->obrigatorio,
            'obrigatorio_label' => $training->obrigatorio ? 'Sim' : 'Não',
            'data_publicacao' => $training->data_publicacao ? $training->data_publicacao->format('d/m/Y') : null,
            'data_liberacao' => $training->data_liberacao ? $training->data_liberacao->format('d/m/Y') : null,
            'total_materiais' => $training->materials()->count(),
        ];
    }

    /**
     * Interpreta o detalhe de um log de submissão e retorna nota e aprovação.
     *
     * @return array{nota: int, aprovado: bool}|null
     */
    protected function parseSubmission(?string $detalhe): ?array
    {
        if (! $detalhe) {
            return null;
        }

        if (preg_match('/Nota (\d+)% \(mínimo (\d+)%\)/', $detalhe, $m)) {
            $nota = (int) $m[1];
            $minimo = (int) $m[2];

            return ['nota' => $nota, 'aprovado' => $nota >= $minimo];
        }

        if (str_contains($detalhe, 'Resposta correta')) {
            return ['nota' => 100, 'aprovado' => true];
        }

        if (str_contains($detalhe, 'Resposta incorreta')) {
            return ['nota' => 0, 'aprovado' => false];
        }

        return null;
    }

    protected function maskCpf(?string $cpf): string
    {
        $cpf = preg_replace('/\D/', '', (string) $cpf);
        if (strlen($cpf) !== 11) {
            return $cpf ?: '—';
        }

        return substr($cpf, 0, 3).'.***.***-'.substr($cpf, 9);
    }

    protected function secondsToHms(int $seconds): string
    {
        if ($seconds <= 0) {
            return '00:00:00';
        }
        $h = floor($seconds / 3600);
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;

        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }

    protected function composeSummary(array $m, $trainingId = null): string
    {
        $title = $trainingId ? "Treinamento #{$trainingId}" : 'Conjunto de treinamentos';
        $parts = [];
        $parts[] = "Análise gerencial para: {$title}.";
        $parts[] = "Usuários ativos elegíveis: {$m['usuarios_ativos_total']} (ajustado: {$m['usuarios_ativos_total_effective']}), Participantes com certificado: {$m['usuarios_com_certificado']} ({$m['percentual_usuarios_ativos']}% dos efetivos).";
        $parts[] = "Iniciaram: {$m['iniciados']}, Concluíram: {$m['concluidos']} ({$m['percent_concluidos']}%).";
        $parts[] = "Tempo médio assistido: {$m['avg_time_human']}.";
        if ($m['avg_days_to_complete'] !== null) {
            $parts[] = "Dias médios para conclusão: {$m['avg_days_to_complete']} dias.";
        }
        $parts[] = 'Análise finalizada com sucesso.';

        return implode(' ', $parts);
    }
}
