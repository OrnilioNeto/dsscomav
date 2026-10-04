<?php

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Training extends Model
{
    use Auditable;
    use BelongsToTenant;
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
        'carga_horaria_segundos',
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
        'carga_horaria_segundos' => 'integer',
        'dias_validade' => 'integer',
        'obrigatorio' => 'boolean',
    ];

    /**
     * Duração total do treinamento em segundos.
     *
     * Compatível com registros antigos: quando carga_horaria_segundos é nulo,
     * o total continua sendo carga_horaria * 60 (comportamento original).
     */
    public function duracaoSegundos(): int
    {
        return ((int) $this->carga_horaria) * 60 + (int) ($this->carga_horaria_segundos ?? 0);
    }

    /**
     * Rótulo compacto da carga horária (ex.: "20 min", "20 min 30 s").
     */
    public function getCargaHorariaFormatadaAttribute(): string
    {
        $minutos = (int) $this->carga_horaria;
        $segundos = (int) ($this->carga_horaria_segundos ?? 0);

        if ($segundos <= 0) {
            return "{$minutos} min";
        }

        return $minutos > 0 ? "{$minutos} min {$segundos} s" : "{$segundos} s";
    }

    /**
     * Rótulo por extenso da carga horária (ex.: "20 minutos e 30 segundos").
     */
    public function getCargaHorariaFormatadaExtensoAttribute(): string
    {
        $minutos = (int) $this->carga_horaria;
        $segundos = (int) ($this->carga_horaria_segundos ?? 0);

        if ($segundos <= 0) {
            return "{$minutos} minutos";
        }

        if ($minutos <= 0) {
            return "{$segundos} segundos";
        }

        return "{$minutos} minutos e {$segundos} segundos";
    }

    /**
     * Valor do campo único de carga horária no formato MM:SS (ex.: "20:30").
     * Registros antigos sem segundos continuam exibindo apenas os minutos.
     */
    public function getCargaHorariaInputAttribute(): string
    {
        $minutos = (int) $this->carga_horaria;
        $segundos = (int) ($this->carga_horaria_segundos ?? 0);

        return $segundos > 0 ? sprintf('%d:%02d', $minutos, $segundos) : (string) $minutos;
    }

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
        // Base elegível do conteúdo: cadastro, público/atribuição, isenções de
        // férias e férias na liberação (mesma regra dos relatórios).
        $elegiveis = User::kpiEligible()->eligibleForContent($this)->pluck('id');

        $total = $elegiveis->count();

        if ($total === 0) {
            return 0;
        }

        $concluido = $this->progress()
            ->where('concluido', true)
            ->whereIn('user_id', $elegiveis)
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

        return ! empty($this->avaliacao_pergunta)
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
        if (! $this->data_liberacao) {
            return true;
        }

        return Carbon::now(config('app.timezone'))->gte($this->data_liberacao);
    }

    /**
     * Data de referência do conteúdo para ordenação/exibição: a data de
     * liberação agendada (segunda-feira) com fallback para a publicação e,
     * por último, a criação do registro (conteúdos legados).
     */
    public function releaseDate(): ?Carbon
    {
        return $this->data_liberacao ?? $this->data_publicacao ?? $this->created_at;
    }

    /**
     * Acessor para exibição da data de liberação (com os mesmos fallbacks).
     */
    public function getDataLiberacaoExibicaoAttribute(): ?Carbon
    {
        return $this->releaseDate();
    }

    /**
     * Ordena pela data de liberação do conteúdo (fallback: publicação/criação).
     */
    public function scopeOrderByReleaseDate($query, string $direction = 'asc')
    {
        $direction = strtolower($direction) === 'desc' ? 'desc' : 'asc';

        return $query->orderByRaw('COALESCE(data_liberacao, data_publicacao, created_at) '.$direction);
    }
}
