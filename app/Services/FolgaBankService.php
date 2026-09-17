<?php

namespace App\Services;

use App\Models\FolgaLog;
use App\Models\FolgaMovimento;
use App\Models\FolgaSaldoMensal;
use App\Models\User;

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
        $prevMes = $mes === 1 ? 12 : $mes - 1;
        $prevAno = $mes === 1 ? $ano - 1 : $ano;

        return FolgaSaldoMensal::where('user_id', $user->id)
            ->where('mes', $prevMes)
            ->where('ano', $prevAno)
            ->value('saldo_acumulado') ?? 0;
    }

    /**
     * Recalcula e grava o snapshot mensal do motorista.
     */
    public function recalcularMes(User $user, int $mes, int $ano, ?int $userId = null): FolgaSaldoMensal
    {
        $snapshot = $this->rules->computeSnapshot($user, $mes, $ano);

        return FolgaSaldoMensal::updateOrCreate(
            ['user_id' => $user->id, 'mes' => $mes, 'ano' => $ano],
            [
                'previstas' => $snapshot['previstas'],
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
     */
    public function estornarMovimento(FolgaMovimento $movimento, ?int $createdBy = null): FolgaMovimento
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

        $this->recalcularMes($movimento->user, $movimento->referencia_mes, $movimento->referencia_ano, $createdBy);

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
