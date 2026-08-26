<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Carbon\Carbon;

class Training extends Model
{
    use HasFactory;

    protected $table = 'trainings';

    protected $fillable = [
        'titulo',
        'descricao',
        'conteudo_programatico',
        'tipo', // 'dss' ou 'treinamento'
        'tipo_treinamento', // 'inicial', 'periodico', 'eventual' (somente para treinamento)
        'tipo_usuario_permitido', // JSON: ["motorista", "funcionario", "terceirizado"]
        'url_video', // YouTube, Vimeo ou link local
        'tipo_video', // 'youtube', 'vimeo', 'upload'
        'carga_horaria',
        'dias_validade',
        'thumbnail',
        'data_publicacao',
        'data_liberacao',
        'status', // 'ativo' ou 'inativo'
        'obrigatorio',
        'avaliacao_pergunta',
        'avaliacao_opcoes',
        'avaliacao_resposta_correta',
        'quantidade_questoes_prova',
        'nota_minima_aprovacao',
    ];

    protected $casts = [
        'data_publicacao' => 'datetime',
        'data_liberacao' => 'datetime',
        'tipo_usuario_permitido' => 'json',
        'avaliacao_opcoes' => 'json',
        'avaliacao_resposta_correta' => 'integer',
        'quantidade_questoes_prova' => 'integer',
        'nota_minima_aprovacao' => 'integer',
        'dias_validade' => 'integer',
        'obrigatorio' => 'boolean',
    ];

    // Relacionamentos
    public function progress()
    {
        return $this->hasMany(UserProgress::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function exemptions()
    {
        return $this->hasMany(TrainingVacationExemption::class);
    }

    public function materials()
    {
        return $this->hasMany(TrainingMaterial::class)->orderBy('ordem');
    }

    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'training_assignments')->withTimestamps();
    }

    public function questions()
    {
        return $this->hasMany(TrainingQuestion::class, 'training_id', 'id')->orderBy('ordem');
    }

    public function projetoPedagogico()
    {
        return $this->hasOneThrough(
            ProjetoPedagogico::class,
            ProjetoPedagogicoTraining::class,
            'training_id',
            'id',
            'id',
            'projeto_pedagogico_id'
        );
    }

    // Métodos auxiliares
    public function isPermittedFor($tipoUsuario)
    {
        if ($this->tipo_usuario_permitido === 'todos' || $this->tipo_usuario_permitido === null) {
            return true;
        }

        $permitidos = is_array($this->tipo_usuario_permitido)
            ? $this->tipo_usuario_permitido
            : json_decode($this->tipo_usuario_permitido, true) ?? [];

        return in_array($tipoUsuario, $permitidos);
    }

    public function getTaxaConclusao()
    {
        // Para treinamento direcionado, o público-alvo são os funcionários atribuídos
        if ($this->tipo === 'treinamento') {
            $total = $this->assignedUsers()->count();

            if ($total === 0) {
                return 0;
            }

            $concluido = $this->progress()
                ->where('concluido', true)
                ->whereIn('user_id', $this->assignedUsers()->select('users.id'))
                ->distinct('user_id')
                ->count();

            return round(($concluido / $total) * 100, 2);
        }

        $total = User::kpiEligible()
            ->eligibleForTrainingKpi($this)
            ->where('status', 'ativo')
            ->whereIn('tipo_usuario', is_array($this->tipo_usuario_permitido) ? $this->tipo_usuario_permitido : json_decode($this->tipo_usuario_permitido, true) ?? [])
            ->count();

        if ($total === 0) {
            return 0;
        }

        $concluido = $this->progress()
            ->where('concluido', true)
            ->whereHas('user', function ($query) {
                $query->kpiEligible()->eligibleForTrainingKpi($this);
            })
            ->distinct('user_id')
            ->count();

        return round(($concluido / $total) * 100, 2);
    }

    public function getVideoEmbed()
    {
        if ($this->tipo_video === 'youtube') {
            preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/', $this->url_video, $matches);
            $videoId = $matches[1] ?? '';
            return "https://www.youtube.com/embed/{$videoId}?enablejsapi=1&playsinline=1";
        } elseif ($this->tipo_video === 'vimeo') {
            preg_match('/vimeo\.com\/(\d+)/', $this->url_video, $matches);
            $videoId = $matches[1] ?? '';
            return "https://player.vimeo.com/video/{$videoId}";
        }
        return $this->url_video;
    }

    public function hasAssessment(): bool
    {
        if ($this->hasQuestionBank()) {
            return true;
        }

        return !empty($this->avaliacao_pergunta)
            && is_array($this->avaliacao_opcoes)
            && count(array_filter($this->avaliacao_opcoes)) >= 2
            && $this->avaliacao_resposta_correta !== null;
    }

    /**
     * Indica se o treinamento possui banco de questões cadastrado.
     */
    public function hasQuestionBank(): bool
    {
        if ($this->relationLoaded('questions')) {
            return $this->questions->count() > 0;
        }

        return $this->questions()->count() > 0;
    }

    /**
     * Retorna o rótulo em pt-BR do tipo do treinamento (inicial/periódico/eventual).
     */
    public function getTipoTreinamentoLabelAttribute(): ?string
    {
        $mapa = [
            'inicial' => 'Inicial',
            'periodico' => 'Periódico',
            'eventual' => 'Eventual',
        ];

        return $this->tipo_treinamento ? ($mapa[$this->tipo_treinamento] ?? ucfirst($this->tipo_treinamento)) : null;
    }

    public function isReleased(): bool
    {
        if (!$this->data_liberacao) {
            return true;
        }

        return Carbon::now(config('app.timezone'))->gte($this->data_liberacao);
    }
}
