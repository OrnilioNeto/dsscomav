<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'training_id',
        'codigo_certificado',
        'data_emissao',
        'data_inicio_assistencia',
        'data_finalizacao_assistencia',
        'tempo_assistido_segundos',
        'porcentagem_assistida',
        'foi_reassistido',
        'caminho_arquivo',
        'valido',
    ];

    protected $casts = [
        'data_emissao' => 'datetime',
        'data_inicio_assistencia' => 'datetime',
        'data_finalizacao_assistencia' => 'datetime',
        'valido' => 'boolean',
        'tempo_assistido_segundos' => 'integer',
        'porcentagem_assistida' => 'integer',
    ];

    // Relacionamentos
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function getValidationUrlAttribute(): string
    {
        return route('validar.certificado', $this->codigo_certificado);
    }

    public function getQrCodeUrlAttribute(): string
    {
        return 'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($this->validation_url);
    }

    // Metodos auxiliares
    public function generateCodigoUnico()
    {
        return strtoupper(
            substr(md5($this->user_id . $this->training_id . time()), 0, 12)
        );
    }

    /**
     * Data em que a validade do treinamento expira (data de emissão + dias de validade configurados).
     * Retorna null quando o treinamento não possui validade definida.
     */
    public function getDataValidadeAttribute()
    {
        if (!$this->training || !$this->training->dias_validade || !$this->data_emissao) {
            return null;
        }

        return $this->data_emissao->copy()->addDays((int) $this->training->dias_validade);
    }

    public function getStatusValidadeAttribute(): string
    {
        $dataValidade = $this->data_validade;
        if (!$dataValidade) {
            return 'sem_validade';
        }

        if ($dataValidade->isPast()) {
            return 'vencido';
        }

        if ($dataValidade->lte(now()->addDays(30))) {
            return 'vencendo';
        }

        return 'valido';
    }

    /**
     * Contagem de certificados de treinamentos com validade crítica
     * (vencidos ou vencendo em até 30 dias) para alerta no menu.
     */
    public static function contarValidadesCriticas(int $dias = 30): int
    {
        try {
            if (!Schema::hasTable('certificates')) {
                return 0;
            }

            $treinamentosComValidade = Training::whereNotNull('dias_validade')
                ->where('dias_validade', '>', 0)
                ->get();

            if ($treinamentosComValidade->isEmpty()) {
                return 0;
            }

            $treinamentosPorId = $treinamentosComValidade->keyBy('id');

            $certificados = self::where('valido', true)
                ->whereIn('training_id', $treinamentosPorId->keys())
                ->get(['id', 'training_id', 'data_emissao']);

            $limite = now()->addDays($dias);
            $total = 0;

            foreach ($certificados as $certificado) {
                $training = $treinamentosPorId->get($certificado->training_id);
                if (!$training || !$certificado->data_emissao) {
                    continue;
                }

                $dataValidade = $certificado->data_emissao->copy()->addDays((int) $training->dias_validade);
                if ($dataValidade->lte($limite)) {
                    $total++;
                }
            }

            return $total;
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
