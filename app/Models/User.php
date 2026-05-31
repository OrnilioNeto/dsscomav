<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'nome',
        'cpf',
        'email',
        'password',
        'telefone',
        'data_nascimento',
        'tipo_usuario',
        'status',
        'role_id',
        'participa_treinamentos',
        'cnh',
        'categoria_cnh',
        'validade_cnh',
        'setor',
        'cargo',
        'empresa',
        'responsavel',
        'ferias_inicio',
        'ferias_fim',
        'usuario_teste',
        'foto_perfil',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'data_nascimento' => 'date',
        'validade_cnh' => 'date',
        'ferias_inicio' => 'date',
        'ferias_fim' => 'date',
        'usuario_teste' => 'boolean',
    ];

    // Relacionamentos
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function progress()
    {
        return $this->hasMany(UserProgress::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    // Métodos auxiliares
    public function isSuperAdmin()
    {
        return $this->role?->nome === 'super_admin';
    }

    public function isAdmin()
    {
        return in_array($this->role?->nome, ['super_admin', 'admin']);
    }

    public function canAccessTraining(Training $training)
    {
        if ($this->isSuperAdmin() || $this->isAdmin()) {
            return true;
        }

        // Regra de bloqueio para novos cadastrados:
        // o usuário só pode acessar conteúdos publicados/liberados a partir
        // da segunda-feira da semana em que ele foi cadastrado.
        $cutoffDate = $this->getTrainingCutoffDate();
        $trainingDate = $training->data_liberacao
            ?? $training->data_publicacao
            ?? $training->created_at;

        if ($cutoffDate && $trainingDate && Carbon::parse($trainingDate)->lt($cutoffDate)) {
            return false;
        }

        if ($training->tipo_usuario_permitido === 'todos') {
            return true;
        }

        $permitidos = is_array($training->tipo_usuario_permitido) 
            ? $training->tipo_usuario_permitido 
            : json_decode($training->tipo_usuario_permitido, true) ?? [];

        return in_array($this->tipo_usuario, $permitidos);
    }

    public function getTrainingCutoffDate(): ?Carbon
    {
        if (!$this->created_at) {
            return null;
        }

        return $this->created_at->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
    }

    public function scopeEligibleForTrainingKpi($query, Training $training)
    {
        $trainingDate = $training->data_liberacao
            ?? $training->data_publicacao
            ?? $training->created_at;

        if (!$trainingDate) {
            return $query;
        }

        // Para um treinamento com data fixa, são elegíveis para KPI os usuários
        // cadastrados até o fim da semana dessa data (domingo 23:59:59).
        $maxUserCreatedAt = Carbon::parse($trainingDate, config('app.timezone'))
            ->endOfWeek(Carbon::SUNDAY)
            ->endOfDay();

        return $query->where('created_at', '<=', $maxUserCreatedAt);
    }

    public function getCpfFormatted()
    {
        return preg_replace('/(\d{3})(\d{3})(\d{3})(\d{2})/', '$1.$2.$3-$4', $this->cpf);
    }

    public function isTestUser(): bool
    {
        return (bool) $this->usuario_teste;
    }

    public function isOnVacation(?Carbon $referenceDate = null): bool
    {
        if (! $this->ferias_inicio || ! $this->ferias_fim) {
            return false;
        }

        $referenceDate = $referenceDate ?: Carbon::now(config('app.timezone'));

        return $referenceDate->betweenIncluded($this->ferias_inicio, $this->ferias_fim);
    }

    public function hasProgressDuringPeriod(?Carbon $periodStart = null, ?Carbon $periodEnd = null): bool
    {
        $periodStart = $periodStart ? $periodStart->copy()->startOfDay() : Carbon::now(config('app.timezone'))->startOfDay();
        $periodEnd = $periodEnd ? $periodEnd->copy()->endOfDay() : Carbon::now(config('app.timezone'))->endOfDay();

        return $this->progress()
            ->where(function ($query) use ($periodStart, $periodEnd) {
                $query->whereBetween('data_inicio', [$periodStart, $periodEnd])
                    ->orWhereBetween('data_conclusao', [$periodStart, $periodEnd]);
            })
            ->exists();
    }

    public function scopeKpiEligible($query, $periodStart = null, $periodEnd = null)
    {
        // SEMPRE excluir: super_admin, usuario_teste, admin sem participação e usuários em férias (agora)
        $now = Carbon::now(config('app.timezone'));

        return $query
            ->where(function ($roleQuery) {
                $roleQuery->whereNull('role_id')
                    ->orWhereHas('role', function ($role) {
                        $role->where('nome', '<>', 'super_admin');
                    });
            })
            ->where('usuario_teste', false)
            ->where(function ($adminParticipationQuery) {
                $adminParticipationQuery->whereDoesntHave('role', function ($role) {
                    $role->where('nome', 'admin');
                })->orWhere('participa_treinamentos', true);
            })
            ->where(function ($vacationQuery) use ($now) {
                // Excluir quem está em férias agora
                $vacationQuery->whereNull('ferias_inicio')
                    ->orWhereNull('ferias_fim')
                    ->orWhere(function ($notVacationQuery) use ($now) {
                        $notVacationQuery->whereDate('ferias_inicio', '>', $now->toDateString())
                            ->orWhereDate('ferias_fim', '<', $now->toDateString());
                    });
            });
    }

    public function scopeVacationInPeriod($query, $periodStart = null, $periodEnd = null)
    {
        $periodStart = $periodStart ? Carbon::parse($periodStart, config('app.timezone'))->startOfDay() : Carbon::now(config('app.timezone'))->startOfDay();
        $periodEnd = $periodEnd ? Carbon::parse($periodEnd, config('app.timezone'))->endOfDay() : Carbon::now(config('app.timezone'))->endOfDay();

        return $query
            ->where(function ($roleQuery) {
                $roleQuery->whereNull('role_id')
                    ->orWhereHas('role', function ($role) {
                        $role->whereNotIn('nome', ['admin', 'super_admin']);
                    });
            })
            ->where('usuario_teste', false)
            ->whereNotNull('ferias_inicio')
            ->whereDate('ferias_inicio', '<=', $periodEnd->toDateString())
            ->where(function ($rangeQuery) use ($periodStart) {
                $rangeQuery->whereNull('ferias_fim')
                    ->orWhereDate('ferias_fim', '>=', $periodStart->toDateString());
            });
    }

    /**
     * Retorna a URL da foto de perfil ou um avatar padrão
     */
    public function getFotoPerfilUrl(): string
    {
        if ($this->foto_perfil && file_exists(public_path("uploads/perfil/{$this->foto_perfil}"))) {
            return asset("uploads/perfil/{$this->foto_perfil}");
        }

        // Avatar colorido baseado nas iniciais do nome
        $initials = $this->getInitials();
        $color = $this->getAvatarColor();

        return "https://ui-avatars.com/api/?name=" . urlencode($initials) . "&background=" . ltrim($color, '#') . "&color=fff&size=200&bold=true&font-size=0.4";
    }

    /**
     * Retorna as iniciais do nome
     */
    public function getInitials(): string
    {
        $parts = explode(' ', trim($this->nome));
        $initials = '';
        foreach ($parts as $part) {
            if (!empty($part)) {
                $initials .= strtoupper($part[0]);
                if (strlen($initials) >= 2) {
                    break;
                }
            }
        }
        return $initials ?: 'U';
    }

    /**
     * Retorna cor consistente baseada no ID do usuário
     */
    public function getAvatarColor(): string
    {
        $colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E2'];
        return $colors[$this->id % count($colors)];
    }
}
