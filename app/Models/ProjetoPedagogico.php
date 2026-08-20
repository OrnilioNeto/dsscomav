<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjetoPedagogico extends Model
{
    use HasFactory;

    protected $table = 'training_projetos_pedagogicos';

    protected $fillable = [
        'training_id',
        'versao',
        'objetivo_geral',
        'principios_sst',
        'estrategia_pedagogica',
        'conteudo_programatico_pp',
        'objetivo_modulos',
        'carga_horaria_pp',
        'tempo_minimo_diario',
        'prazo_maximo_conclusao',
        'publico_alvo',
        'material_didatico',
        'instrumentos_aprendizado',
        'avaliacao_aprendizagem',
        'instrutores',
        'infraestrutura_operacional',
        'responsavel_tecnico_nome',
        'responsavel_tecnico_qualificacao',
        'data_validacao',
        'data_proxima_revisao',
        'arquivo_pdf',
        'assinatura_rt',
        'assinatura_rt_nome',
        'assinatura_rt_data',
    ];

    protected $casts = [
        'data_validacao' => 'date',
        'data_proxima_revisao' => 'date',
        'assinatura_rt_data' => 'datetime',
    ];

    public function training()
    {
        return $this->belongsTo(Training::class, 'training_id', 'id');
    }

    /**
     * Status da revisão do projeto pedagógico (Anexo II 3.3).
     */
    public function getStatusRevisaoAttribute(): string
    {
        if (!$this->data_proxima_revisao) {
            return 'sem_revisao';
        }

        if ($this->data_proxima_revisao->isPast()) {
            return 'vencida';
        }

        if ($this->data_proxima_revisao->lte(now()->addDays(60))) {
            return 'proxima';
        }

        return 'ok';
    }

    /**
     * Percentual de preenchimento dos campos obrigatórios do Anexo II 3.1.
     */
    public function getPercentualPreenchimentoAttribute(): int
    {
        $campos = [
            'objetivo_geral', 'principios_sst', 'estrategia_pedagogica', 'conteudo_programatico_pp',
            'objetivo_modulos', 'carga_horaria_pp', 'tempo_minimo_diario', 'prazo_maximo_conclusao',
            'publico_alvo', 'material_didatico', 'instrumentos_aprendizado', 'avaliacao_aprendizagem',
            'instrutores', 'infraestrutura_operacional', 'responsavel_tecnico_nome',
        ];

        $preenchidos = 0;
        foreach ($campos as $campo) {
            if (!empty(trim((string) $this->{$campo}))) {
                $preenchidos++;
            }
        }

        return (int) round(($preenchidos / count($campos)) * 100);
    }
}