<?php

namespace App\Services;

use App\Models\FolgaDia;
use App\Models\FolgaProgramacao;
use App\Models\Training;
use App\Models\User;
use Carbon\Carbon;
use Closure;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;

/**
 * Regra de isenção de treinamento por afastamento de semana completa.
 *
 * O colaborador tem a semana inteira (segunda a domingo) para concluir o
 * conteúdo. Por isso, apenas afastamentos que cubram os 7 dias da semana de
 * liberação/conclusão isentam o treinamento. Afastamentos parciais não mudam
 * a pendência.
 *
 * A cobertura pode vir de:
 * - 7 registros em folga_dias (folga/atestado/licença); ou
 * - uma programação ativa (ou cancelada após o fim da semana) cobrindo o
 *   intervalo inteiro.
 */
class TrainingAbsenceService
{
    public const TIPOS = ['folga', 'atestado', 'licenca'];

    public static function cutoff(): ?Carbon
    {
        $value = config('folgas.isencao_treinamento_desde');

        if ($value === null || $value === '') {
            return null;
        }

        try {
            return Carbon::parse($value, config('app.timezone'))->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Semana (segunda 00:00 a domingo 23:59) do prazo do conteúdo.
     *
     * @return array{0: Carbon, 1: Carbon}|null
     */
    public static function semanaDoConteudo(Training $training): ?array
    {
        $inicio = $training->prazoSemanaInicio();
        $fim = $training->prazoSemanaFim();

        return $inicio && $fim ? [$inicio, $fim] : null;
    }

    /**
     * Carrega os lançamentos do usuário. A data de corte e os tipos são
     * aplicados em coberturaCompleta()/rotuloCobertura(); aqui carregamos
     * também os dias de "trabalho", que anulam uma programação.
     *
     * @return array{dias: Collection<int, FolgaDia>, programacoes: Collection<int, FolgaProgramacao>}
     */
    public static function dadosAusencia(User $user): array
    {
        $diasQuery = FolgaDia::query()->where('user_id', $user->id);

        $programacoesQuery = FolgaProgramacao::query()
            ->where('user_id', $user->id)
            ->whereIn('tipo', self::TIPOS);

        return [
            'dias' => $diasQuery->get(),
            'programacoes' => $programacoesQuery->get(),
        ];
    }

    public static function isento(User $user, Training $training): bool
    {
        $dados = self::dadosAusencia($user);

        return self::isentoComDados($dados['dias'], $dados['programacoes'], $training);
    }

    /**
     * @param  Collection<int, FolgaDia>  $dias
     * @param  Collection<int, FolgaProgramacao>  $programacoes
     */
    public static function isentoComDados(Collection $dias, Collection $programacoes, Training $training): bool
    {
        $semana = self::semanaDoConteudo($training);

        if ($semana === null) {
            return false;
        }

        return self::coberturaCompleta($dias, $programacoes, $semana[0], $semana[1]);
    }

    /**
     * A semana só é considerada coberta quando os 7 dias estão cobertos por
     * lançamentos de ausência ou por uma única programação que pega o
     * intervalo inteiro.
     *
     * @param  Collection<int, FolgaDia>  $dias
     * @param  Collection<int, FolgaProgramacao>  $programacoes
     */
    public static function coberturaCompleta(Collection $dias, Collection $programacoes, Carbon $inicio, Carbon $fim): bool
    {
        $cutoff = self::cutoff();
        $primeiro = $inicio->copy()->startOfDay()->toDateString();
        $ultimo = $fim->copy()->endOfDay()->toDateString();

        $cobertos = [];
        $trabalhoNoPeriodo = false;

        foreach ($dias as $dia) {
            if (! $dia instanceof FolgaDia || ! $dia->data) {
                continue;
            }

            $data = $dia->data->format('Y-m-d');

            if ($data < $primeiro || $data > $ultimo) {
                continue;
            }

            if ($dia->tipo === 'trabalho') {
                $trabalhoNoPeriodo = true;

                continue;
            }

            if (! in_array($dia->tipo, self::TIPOS, true) || ! self::lancadoAposCutoff($dia, $cutoff)) {
                continue;
            }

            $cobertos[$data] = true;
        }

        if (count($cobertos) >= 7) {
            return true;
        }

        // Um dia de trabalho explícito vence a programação (mesma regra do
        // banco de folgas), então a programação não cobre a semana inteira.
        if ($trabalhoNoPeriodo) {
            return false;
        }

        foreach ($programacoes as $programacao) {
            if (! $programacao instanceof FolgaProgramacao
                || ! in_array($programacao->tipo, self::TIPOS, true)
                || ! $programacao->data_inicio
                || ! $programacao->data_fim
                || ! self::programacaoAposCutoff($programacao, $cutoff)) {
                continue;
            }

            $fimEfetivo = $programacao->status === 'cancelada' && $programacao->cancelada_em
                ? $programacao->cancelada_em->copy()->startOfDay()->subDay()
                : $programacao->data_fim->copy()->startOfDay();

            if ($programacao->data_inicio->toDateString() <= $primeiro
                && $fimEfetivo->toDateString() >= $ultimo) {
                return true;
            }
        }

        return false;
    }

    /**
     * Rótulo do tipo de afastamento que cobriu a semana (para relatórios).
     *
     * @param  Collection<int, FolgaDia>  $dias
     * @param  Collection<int, FolgaProgramacao>  $programacoes
     */
    public static function rotuloCobertura(Collection $dias, Collection $programacoes, Carbon $inicio, Carbon $fim): ?string
    {
        $cutoff = self::cutoff();
        $primeiro = $inicio->copy()->startOfDay()->toDateString();
        $ultimo = $fim->copy()->endOfDay()->toDateString();

        $tipos = [];
        $diasCobertos = [];
        $trabalhoNoPeriodo = false;

        foreach ($dias as $dia) {
            if (! $dia instanceof FolgaDia || ! $dia->data) {
                continue;
            }

            $data = $dia->data->format('Y-m-d');

            if ($data < $primeiro || $data > $ultimo) {
                continue;
            }

            if ($dia->tipo === 'trabalho') {
                $trabalhoNoPeriodo = true;

                continue;
            }

            if (! in_array($dia->tipo, self::TIPOS, true) || ! self::lancadoAposCutoff($dia, $cutoff)) {
                continue;
            }

            $diasCobertos[$data] = true;
            $tipos[$dia->tipo] = true;
        }

        if (count($diasCobertos) >= 7) {
            return self::rotuloTipos(array_keys($tipos));
        }

        if ($trabalhoNoPeriodo) {
            return null;
        }

        foreach ($programacoes as $programacao) {
            if (! $programacao instanceof FolgaProgramacao
                || ! in_array($programacao->tipo, self::TIPOS, true)
                || ! $programacao->data_inicio
                || ! $programacao->data_fim
                || ! self::programacaoAposCutoff($programacao, $cutoff)) {
                continue;
            }

            $fimEfetivo = $programacao->status === 'cancelada' && $programacao->cancelada_em
                ? $programacao->cancelada_em->copy()->startOfDay()->subDay()
                : $programacao->data_fim->copy()->startOfDay();

            if ($programacao->data_inicio->toDateString() <= $primeiro
                && $fimEfetivo->toDateString() >= $ultimo) {
                return self::rotuloTipos([$programacao->tipo]);
            }
        }

        return null;
    }

    /**
     * Aplica o "NOT EXISTS" das ausências que cobrem a semana do conteúdo.
     */
    public static function aplicarEscopoAusencia(EloquentBuilder $query, Training $training): void
    {
        $semana = self::semanaDoConteudo($training);

        if ($semana === null) {
            return;
        }

        [$inicio, $fim] = $semana;

        $query->whereNotExists(self::subFolgaDiasCompleta($inicio, $fim));
        $query->whereNotExists(self::subProgramacaoCompleta($inicio, $fim));
    }

    /**
     * Filtra somente quem está coberto por uma ausência de semana completa.
     * Usado nos contadores de justificativas do relatório de auditoria.
     */
    public static function aplicarEscopoApenasAusencia(EloquentBuilder $query, Training $training): void
    {
        $semana = self::semanaDoConteudo($training);

        if ($semana === null) {
            $query->whereRaw('1 = 0');

            return;
        }

        [$inicio, $fim] = $semana;

        $query->where(function ($q) use ($inicio, $fim) {
            $q->whereExists(self::subFolgaDiasCompleta($inicio, $fim))
                ->orWhereExists(self::subProgramacaoCompleta($inicio, $fim));
        });
    }

    /**
     * @param  QueryBuilder|EloquentBuilder  $query
     */
    public static function aplicarCutoffLancamento($query, ?Carbon $cutoff): void
    {
        if (! $cutoff) {
            return;
        }

        $query->where(function ($q) use ($cutoff) {
            $q->where('lancado_em', '>=', $cutoff)
                ->orWhere(function ($q2) use ($cutoff) {
                    $q2->whereNull('lancado_em')
                        ->where('created_at', '>=', $cutoff);
                });
        });
    }

    /**
     * @param  QueryBuilder|EloquentBuilder  $query
     */
    public static function aplicarCutoffProgramacao($query, ?Carbon $cutoff): void
    {
        if (! $cutoff) {
            return;
        }

        $query->where('created_at', '>=', $cutoff);
    }

    private static function subFolgaDiasCompleta(Carbon $inicio, Carbon $fim): Closure
    {
        return function (QueryBuilder $sub) use ($inicio, $fim) {
            $sub->selectRaw('1')
                ->from('folga_dias')
                ->whereColumn('folga_dias.user_id', 'users.id')
                ->whereIn('folga_dias.tipo', self::TIPOS)
                ->whereDate('folga_dias.data', '>=', $inicio->toDateString())
                ->whereDate('folga_dias.data', '<=', $fim->toDateString())
                ->whereTenant('folga_dias');

            self::aplicarCutoffLancamento($sub, self::cutoff());

            $sub->groupBy('folga_dias.user_id')
                ->havingRaw('COUNT(DISTINCT folga_dias.data) >= 7');
        };
    }

    private static function subProgramacaoCompleta(Carbon $inicio, Carbon $fim): Closure
    {
        return function (QueryBuilder $sub) use ($inicio, $fim) {
            $sub->selectRaw('1')
                ->from('folga_programacoes')
                ->whereColumn('folga_programacoes.user_id', 'users.id')
                ->whereIn('folga_programacoes.tipo', self::TIPOS)
                ->whereDate('folga_programacoes.data_inicio', '<=', $inicio->toDateString())
                ->whereDate('folga_programacoes.data_fim', '>=', $fim->toDateString())
                ->whereTenant('folga_programacoes')
                ->whereNotExists(function (QueryBuilder $trabalho) use ($inicio, $fim) {
                    $trabalho->selectRaw('1')
                        ->from('folga_dias')
                        ->whereColumn('folga_dias.user_id', 'folga_programacoes.user_id')
                        ->where('folga_dias.tipo', 'trabalho')
                        ->whereDate('folga_dias.data', '>=', $inicio->toDateString())
                        ->whereDate('folga_dias.data', '<=', $fim->toDateString())
                        ->whereTenant('folga_dias');
                });

            self::aplicarCutoffProgramacao($sub, self::cutoff());

            $sub->where(function ($q) use ($fim) {
                $q->where('folga_programacoes.status', 'ativa')
                    ->orWhere(function ($q2) use ($fim) {
                        $q2->where('folga_programacoes.status', 'cancelada')
                            ->where(function ($q3) use ($fim) {
                                $q3->whereNull('folga_programacoes.cancelada_em')
                                    ->orWhereDate('folga_programacoes.cancelada_em', '>', $fim->toDateString());
                            });
                    });
            });
        };
    }

    private static function lancadoAposCutoff(FolgaDia $dia, ?Carbon $cutoff): bool
    {
        if (! $cutoff) {
            return true;
        }

        $referencia = $dia->lancado_em ?? $dia->created_at;

        if (! $referencia) {
            return true;
        }

        try {
            return Carbon::parse($referencia)->gte($cutoff);
        } catch (\Throwable) {
            return true;
        }
    }

    private static function programacaoAposCutoff(FolgaProgramacao $programacao, ?Carbon $cutoff): bool
    {
        if (! $cutoff) {
            return true;
        }

        if (! $programacao->created_at) {
            return true;
        }

        try {
            return Carbon::parse($programacao->created_at)->gte($cutoff);
        } catch (\Throwable) {
            return true;
        }
    }

    /**
     * @param  array<int, string>  $tipos
     */
    private static function rotuloTipos(array $tipos): string
    {
        $labels = [
            'folga' => 'Folga',
            'atestado' => 'Atestado',
            'licenca' => 'Licença',
        ];

        $nomes = array_map(fn ($tipo) => $labels[$tipo] ?? ucfirst((string) $tipo), $tipos);
        sort($nomes);

        return implode(' + ', array_unique($nomes));
    }
}
