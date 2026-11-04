<?php

namespace App\Modules\Frota\Services;

use App\Models\User;
use App\Modules\Frota\Models\FrotaConfiguracao;
use App\Modules\Frota\Models\FrotaLiberacao;
use App\Modules\Frota\Models\FrotaMotorista;
use App\Support\TenantManager;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Decide se o usuário pode acessar os treinamentos.
 *
 * Regra:
 *  - não motorista / gestor / integração desativada => libera
 *  - liberação manual vigente => libera (sobrepõe tudo)
 *  - motorista com sessão aberta no rastreador e < limite de horas => libera
 *  - API indisponível => grace (último online recente), open ou closed
 *  - demais casos => bloqueia
 */
class DriverPresenceService
{
    private JornadaSyncService $sync;

    public function __construct(?JornadaSyncService $sync = null)
    {
        // Timeout curto: a checagem roda no caminho da request (login/gate).
        $this->sync = $sync ?? new JornadaSyncService(new AutotracClient(null, 3, 2));
    }

    /**
     * @return array{
     *     liberado: bool,
     *     origem: string,
     *     motivo: string,
     *     detalhe: ?string,
     *     verificado_em: ?Carbon,
     *     liberacao: ?FrotaLiberacao,
     *     motorista: ?FrotaMotorista,
     *     horas_sessao: ?float
     * }
     */
    public function avaliar(User $user, bool $forcar = false): array
    {
        // Kill switch global: com a verificação desativada, ninguém é bloqueado
        // e não há consulta ao banco/API no caminho da request.
        if (! config('autotrac.gate_enabled', false)) {
            return $this->resultado(
                true,
                'desativado',
                'A verificação de jornada está desativada temporariamente.'
            );
        }

        $config = FrotaConfiguracao::atual();

        if (! $config->ativo) {
            return $this->resultado(true, 'integracao_desativada', 'A verificação de jornada está desativada.');
        }

        $tipos = $config->tiposMotorista();

        if (! in_array((string) $user->tipo_usuario, $tipos, true)) {
            return $this->resultado(true, 'nao_motorista', 'A regra de jornada não se aplica ao seu perfil.');
        }

        if ($user->isAdmin()) {
            return $this->resultado(true, 'gestor', 'Perfil de gestão não é afetado pela regra de jornada.');
        }

        $liberacao = $this->liberacaoAtiva($user);

        if ($liberacao) {
            return $this->resultado(
                true,
                'manual',
                'Acesso liberado manualmente pela gestão.',
                $this->textoLiberacao($liberacao),
                $liberacao->fim,
                $liberacao
            );
        }

        $motorista = $this->motoristaDoUsuario($user);

        if (! $motorista) {
            return $this->resultado(
                false,
                'sem_cadastro',
                'Não identificamos seu cadastro de motorista no rastreamento. Fale com seu gestor para liberar o acesso.'
            );
        }

        $presenca = $this->presenca($motorista, $config, $forcar);
        $horas = $motorista->horasSessao();

        if ($presenca['indisponivel']) {
            $failMode = (string) ($config->fail_mode ?: 'grace');

            if ($failMode === 'open') {
                return $this->resultado(
                    true,
                    'fail_open',
                    'Não foi possível confirmar a jornada agora; o acesso foi mantido.',
                    'Integração indisponível.',
                    $motorista->verificado_em,
                    null,
                    $motorista,
                    $horas
                );
            }

            if ($failMode === 'grace' && $this->dentroDaTolerancia($motorista, $config)) {
                return $this->resultado(
                    true,
                    'grace',
                    'Não foi possível confirmar a jornada agora; mantivemos seu último status por tolerância.',
                    'Integração indisponível.',
                    $motorista->verificado_em,
                    null,
                    $motorista,
                    $horas
                );
            }

            return $this->resultado(
                false,
                'integracao_indisponivel',
                'Não foi possível confirmar sua jornada no rastreamento agora. Fale com seu gestor.',
                $motorista->ultimo_erro,
                $motorista->verificado_em,
                null,
                $motorista,
                $horas
            );
        }

        if ($presenca['online']) {
            $limite = max(1, (int) $config->jornada_maxima_horas);

            if ($horas !== null && $horas >= $limite) {
                return $this->resultado(
                    false,
                    'jornada_excedida',
                    sprintf(
                        'Sua sessão no rastreamento está aberta há %.1fh (limite de %dh). Fale com seu gestor.',
                        $horas,
                        $limite
                    ),
                    null,
                    $motorista->verificado_em,
                    null,
                    $motorista,
                    $horas
                );
            }

            return $this->resultado(
                true,
                'autotrac',
                'Jornada identificada no rastreamento.',
                $motorista->veiculoAtual?->identificacao,
                $motorista->verificado_em,
                null,
                $motorista,
                $horas
            );
        }

        return $this->resultado(
            false,
            'offline',
            'Não identificamos uma jornada em andamento no rastreamento. Os treinamentos ficam disponíveis apenas durante o seu horário de trabalho.',
            null,
            $motorista->verificado_em,
            null,
            $motorista,
            $horas
        );
    }

    public function podeAcessarTreinamentos(User $user): bool
    {
        return $this->avaliar($user)['liberado'];
    }

    public function liberacaoAtiva(User $user): ?FrotaLiberacao
    {
        return FrotaLiberacao::query()
            ->vigente()
            ->where('user_id', $user->id)
            ->orderByDesc('fim')
            ->first();
    }

    public function motoristaDoUsuario(User $user): ?FrotaMotorista
    {
        $motorista = FrotaMotorista::query()->where('user_id', $user->id)->first();

        if ($motorista) {
            return $motorista;
        }

        $cpf = preg_replace('/\D/', '', (string) $user->cpf) ?? '';

        if ($cpf === '') {
            return null;
        }

        $motorista = FrotaMotorista::query()->where('cpf', $cpf)->first();

        if ($motorista) {
            if ($motorista->user_id === null) {
                $motorista->forceFill(['user_id' => $user->id])->save();
            }

            return $motorista;
        }

        return $this->cadastrarPelaApi($user);
    }

    /**
     * Fallback antes do primeiro sync: procura o CPF direto no driverstatus.
     */
    private function cadastrarPelaApi(User $user): ?FrotaMotorista
    {
        return $this->sync->cadastrarMotoristaPorCpf($user);
    }

    /**
     * @return array{online: bool, indisponivel: bool}
     */
    private function presenca(FrotaMotorista $motorista, FrotaConfiguracao $config, bool $forcar): array
    {
        $ttl = max(10, (int) $config->ttl_segundos);

        if (! $forcar && $motorista->verificado_em && $motorista->verificado_em->gte(now()->subSeconds($ttl))) {
            return ['online' => $motorista->estaOnline(), 'indisponivel' => false];
        }

        $chave = sprintf('frota:presence:%s:%s', app(TenantManager::class)->id() ?? 'single', $motorista->id);

        // Evita rajada de chamadas em requests paralelos; usa o último status.
        if (! $forcar && ! Cache::add($chave, 1, 20)) {
            return ['online' => $motorista->estaOnline(), 'indisponivel' => false];
        }

        $resultado = $this->sync->verificarMotorista($motorista);
        $motorista->refresh();

        return [
            'online' => $resultado['online'],
            'indisponivel' => $resultado['indisponivel'],
        ];
    }

    private function dentroDaTolerancia(FrotaMotorista $motorista, FrotaConfiguracao $config): bool
    {
        if (! $motorista->ultimo_online_em) {
            return false;
        }

        $minutos = max(1, (int) $config->grace_minutos);

        return $motorista->ultimo_online_em->gte(now()->subMinutes($minutos));
    }

    private function textoLiberacao(FrotaLiberacao $liberacao): string
    {
        $partes = ['Liberado até '.$liberacao->fim?->format('d/m/Y H:i')];

        if (filled($liberacao->motivo)) {
            $partes[] = 'Motivo: '.$liberacao->motivo;
        }

        return implode(' — ', $partes);
    }

    /**
     * @return array{
     *     liberado: bool,
     *     origem: string,
     *     motivo: string,
     *     detalhe: ?string,
     *     verificado_em: ?Carbon,
     *     liberacao: ?FrotaLiberacao,
     *     motorista: ?FrotaMotorista,
     *     horas_sessao: ?float
     * }
     */
    private function resultado(
        bool $liberado,
        string $origem,
        string $motivo,
        ?string $detalhe = null,
        $verificadoEm = null,
        ?FrotaLiberacao $liberacao = null,
        ?FrotaMotorista $motorista = null,
        ?float $horas = null
    ): array {
        return [
            'liberado' => $liberado,
            'origem' => $origem,
            'motivo' => $motivo,
            'detalhe' => $detalhe,
            'verificado_em' => $verificadoEm,
            'liberacao' => $liberacao,
            'motorista' => $motorista,
            'horas_sessao' => $horas,
        ];
    }
}
