<?php

namespace App\Services;

use App\Models\FolgaDia;
use App\Models\FolgaLog;
use App\Models\FolgaMovimento;
use App\Models\FolgaSaldoMensal;
use App\Models\FolgaSetting;
use App\Models\User;
use Carbon\Carbon;

class FolgaBankService
{
    private FolgaRulesService $rules;

    public function __construct(FolgaRulesService $rules)
    {
        $this->rules = $rules;
    }

    /**
     * Saldo acumulado do mês anterior para o motorista.
     */
    public function getSaldoAnterior(User $user, int $mes, int $ano): int
    {
        return $this->rules->getSaldoAnterior($user, $mes, $ano);
    }

    /**
     * Recalcula e grava o snapshot mensal do motorista e, em cascata, os
     * meses seguintes (até o mês atual) — sem isso, edições retroativas em um
     * mês deixariam os snapshots seguintes defasados e o saldo negativo
     * acumulado seria perdido na exibição.
     */
    public function recalcularMes(User $user, int $mes, int $ano, ?int $userId = null): FolgaSaldoMensal
    {
        $salvo = $this->salvarSnapshot($user, $mes, $ano);

        foreach ($this->mesesSeguintes($user, $mes, $ano) as [$m, $a]) {
            $this->salvarSnapshot($user, $m, $a);
        }

        return $salvo;
    }

    /**
     * Recalcula todo o histórico do motorista (do primeiro lançamento até o
     * mês atual), garantindo a cadeia de saldos acumulados.
     */
    public function recalcularHistorico(User $user): int
    {
        [$mes, $ano] = $this->primeiroMesComRegistro($user, (int) now()->month, (int) now()->year);

        $cursor = Carbon::createFromDate($ano, $mes, 1)->startOfMonth();
        $limite = now()->startOfMonth();
        $total = 0;

        while ($cursor->lte($limite)) {
            $this->salvarSnapshot($user, (int) $cursor->month, (int) $cursor->year);
            $cursor->addMonthNoOverflow();
            $total++;
        }

        return $total;
    }

    private function salvarSnapshot(User $user, int $mes, int $ano): FolgaSaldoMensal
    {
        $snapshot = $this->rules->computeSnapshot($user, $mes, $ano);

        return FolgaSaldoMensal::updateOrCreate(
            ['user_id' => $user->id, 'mes' => $mes, 'ano' => $ano],
            [
                'previstas' => $snapshot['previstas_ganhas'],
                'tiradas' => $snapshot['tiradas'],
                'ajustes' => $snapshot['ajustes'],
                'saldo_anterior' => $snapshot['saldo_anterior'],
                'saldo_acumulado' => $snapshot['saldo_acumulado'],
                'domingo_cumprido' => $snapshot['domingo_cumprido'],
                'dias_trabalhados' => $snapshot['dias_trabalhados'],
                'dias_atestado' => $snapshot['dias_atestado'],
                'dias_licenca' => $snapshot['dias_licenca'],
            ]
        );
    }

    /**
     * Meses que precisam ser recalculados depois de alterar o mês informado.
     *
     * @return array<int, array{0:int,1:int}>
     */
    private function mesesSeguintes(User $user, int $mes, int $ano): array
    {
        $alvo = Carbon::createFromDate($ano, $mes, 1)->startOfMonth();
        $limite = now()->startOfMonth();

        if ($alvo->gt($limite)) {
            $limite = $alvo;
        }

        $ultimaData = FolgaDia::where('user_id', $user->id)->max('data');
        if ($ultimaData) {
            $ultimoLancamento = Carbon::parse($ultimaData)->startOfMonth();
            if ($ultimoLancamento->gt($limite)) {
                $limite = $ultimoLancamento;
            }
        }

        // Evita varredura absurda se alguém lançar algo em um ano muito distante.
        $maximo = $alvo->copy()->addMonthsNoOverflow(24);
        if ($limite->gt($maximo)) {
            $limite = $maximo;
        }

        $meses = [];
        $cursor = $alvo->copy()->addMonthNoOverflow();

        while ($cursor->lte($limite)) {
            $meses[] = [(int) $cursor->month, (int) $cursor->year];
            $cursor->addMonthNoOverflow();
        }

        return $meses;
    }

    /**
     * @return array{0:int,1:int}
     */
    private function primeiroMesComRegistro(User $user, int $mesPadrao, int $anoPadrao): array
    {
        // Marco zero do controle manda no início da reconstrução
        $inicioControle = FolgaSetting::firstOrCreateDefault()->data_inicio_controle;
        if ($inicioControle) {
            return [(int) $inicioControle->month, (int) $inicioControle->year];
        }

        $dataDia = FolgaDia::where('user_id', $user->id)->min('data');
        $movimento = FolgaMovimento::where('user_id', $user->id)
            ->orderBy('referencia_ano')
            ->orderBy('referencia_mes')
            ->first();

        $candidatos = [];

        if ($dataDia) {
            $candidatos[] = Carbon::parse($dataDia)->startOfMonth();
        }

        if ($movimento) {
            $candidatos[] = Carbon::createFromDate($movimento->referencia_ano, $movimento->referencia_mes, 1);
        }

        if ($user->ultima_folga_data) {
            $candidatos[] = $user->ultima_folga_data->copy()->startOfMonth();
        }

        if (empty($candidatos)) {
            return [$mesPadrao, $anoPadrao];
        }

        usort($candidatos, fn ($a, $b) => $a->timestamp <=> $b->timestamp);
        $primeiro = $candidatos[0];

        return [(int) $primeiro->month, (int) $primeiro->year];
    }

    /**
     * Registra movimento no banco de folgas.
     */
    public function registrarMovimento(
        User $user,
        string $data,
        string $tipo,
        int $quantidade,
        int $mes,
        int $ano,
        ?string $observacao = null,
        ?int $createdBy = null
    ): FolgaMovimento {
        $mov = FolgaMovimento::create([
            'user_id' => $user->id,
            'data' => $data,
            'tipo' => $tipo,
            'quantidade' => $quantidade,
            'referencia_mes' => $mes,
            'referencia_ano' => $ano,
            'observacao' => $observacao,
            'created_by' => $createdBy,
        ]);

        $this->log('criar_movimento', $user->id, null, null, $mov->toArray(), $createdBy);

        return $mov;
    }

    /**
     * Ajuste manual de saldo (positivo ou negativo) com justificativa.
     */
    public function ajustarSaldo(
        User $user,
        int $quantidade,
        int $mes,
        int $ano,
        string $justificativa,
        ?int $createdBy = null
    ): FolgaMovimento {
        $mov = $this->registrarMovimento(
            $user,
            now()->format('Y-m-d'),
            'ajuste',
            $quantidade,
            $mes,
            $ano,
            $justificativa,
            $createdBy
        );

        $this->recalcularMes($user, $mes, $ano, $createdBy);

        return $mov;
    }

    /**
     * Estorna um movimento existente.
     * Use $recalcular = false para lotes (o chamador recalcula uma única vez).
     */
    public function estornarMovimento(FolgaMovimento $movimento, ?int $createdBy = null, bool $recalcular = true): FolgaMovimento
    {
        $estorno = $this->registrarMovimento(
            $movimento->user,
            now()->format('Y-m-d'),
            'estorno',
            -$movimento->quantidade,
            $movimento->referencia_mes,
            $movimento->referencia_ano,
            "Estorno do movimento #{$movimento->id}: {$movimento->observacao}",
            $createdBy
        );

        $this->log('estorno_movimento', $movimento->user_id, 'folga_movimentos', $movimento->id, $movimento->toArray(), $createdBy);

        if ($recalcular) {
            $this->recalcularMes($movimento->user, $movimento->referencia_mes, $movimento->referencia_ano, $createdBy);
        }

        return $estorno;
    }

    /**
     * Registra log de auditoria.
     */
    public function log(string $acao, int $userId, ?string $tabela, ?int $registroId, ?array $dados, ?int $createdBy): void
    {
        FolgaLog::create([
            'user_id' => $userId,
            'acao' => $acao,
            'tabela' => $tabela,
            'registro_id' => $registroId,
            'dados_depois' => $dados,
            'created_by' => $createdBy,
        ]);
    }
}
