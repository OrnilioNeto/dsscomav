<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolgaProgramacao extends Model
{
    use BelongsToTenant;

    protected $table = 'folga_programacoes';

    protected $fillable = [
        'user_id',
        'data_inicio',
        'data_fim',
        'tipo',
        'motivo',
        'observacao',
        'status',
        'cancelada_em',
        'cancelada_por',
        'created_by',
    ];

    protected $casts = [
        'data_inicio' => 'date',
        'data_fim' => 'date',
        'cancelada_em' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function canceladoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelada_por');
    }

    public function isCancelada(): bool
    {
        return $this->status === 'cancelada';
    }

    /**
     * Último dia em que a programação ainda vale (cancelamento corta o restante).
     */
    public function dataFimEfetiva(): Carbon
    {
        if ($this->isCancelada() && $this->cancelada_em) {
            return $this->cancelada_em->copy()->subDay();
        }

        return $this->data_fim->copy();
    }

    /**
     * Último dia que já deve ser debitado (só debita quando a data chega).
     */
    public function dataFimDebitavel(?Carbon $hoje = null): Carbon
    {
        $hoje = ($hoje ?? now())->copy()->startOfDay();
        $fim = $this->dataFimEfetiva();

        return $fim->gt($hoje) ? $hoje->copy() : $fim;
    }

    /**
     * Dias do intervalo que já foram efetivados (data já chegou).
     *
     * @return array<int, string>
     */
    public function diasEfetivados(?Carbon $hoje = null): array
    {
        $fim = $this->dataFimDebitavel($hoje);

        if ($fim->lt($this->data_inicio)) {
            return [];
        }

        $dias = [];
        $cursor = $this->data_inicio->copy();

        while ($cursor->lte($fim)) {
            $dias[] = $cursor->format('Y-m-d');
            $cursor->addDay();
        }

        return $dias;
    }
}
