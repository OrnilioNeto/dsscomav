<?php

namespace App\Modules\CronogramaConteudo\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Training;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CronogramaConteudo extends Model
{
    use Auditable;
    use BelongsToTenant;
    use HasFactory;

    protected $table = 'cronograma_conteudos';

    protected $fillable = [
        'nome',
        'data_prevista_liberacao',
        'training_id',
        'observacoes',
        'created_by',
        'pptx_ok',
        'pdf_ok',
        'video_ok',
        'youtube_ok',
    ];

    protected $casts = [
        'data_prevista_liberacao' => 'date',
        'pptx_ok' => 'boolean',
        'pdf_ok' => 'boolean',
        'video_ok' => 'boolean',
        'youtube_ok' => 'boolean',
    ];

    public function training(): BelongsTo
    {
        return $this->belongsTo(Training::class);
    }

    /**
     * Treinamento vinculado (explícito ou por correspondência de nome).
     */
    public function matchedTraining(): ?Training
    {
        if ($this->training_id) {
            $training = Training::find($this->training_id);
            if ($training) {
                return $training;
            }
        }

        $nome = trim((string) $this->nome);
        if ($nome === '') {
            return null;
        }

        $exact = Training::whereRaw('LOWER(titulo) = ?', [mb_strtolower($nome)])->first();
        if ($exact) {
            return $exact;
        }

        return Training::where('titulo', 'like', '%'.$nome.'%')->first();
    }

    /**
     * Rótulo de status calculado automaticamente.
     *
     * - Sem data de previsão: Listado
     * - Com data, sem treinamento vinculado: Previsão
     * - Vinculado a treinamento criado: Programado
     * - Ao chegar na data/hora de liberação: Realizado
     */
    public function statusLabel(): string
    {
        if (! $this->data_prevista_liberacao) {
            return 'Listado';
        }

        $dataLiberacao = Carbon::parse($this->data_prevista_liberacao, config('app.timezone'))
            ->startOfDay();
        $hoje = Carbon::now(config('app.timezone'))->startOfDay();

        $training = $this->matchedTraining();

        if ($dataLiberacao->lte($hoje)) {
            return 'Realizado';
        }

        if ($training) {
            return 'Programado';
        }

        return 'Previsão';
    }

    public function statusColor(): string
    {
        return match ($this->statusLabel()) {
            'Realizado' => 'bg-green-100 text-green-800',
            'Programado' => 'bg-blue-100 text-blue-800',
            'Previsão' => 'bg-yellow-100 text-yellow-800',
            'Listado' => 'bg-slate-100 text-slate-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}