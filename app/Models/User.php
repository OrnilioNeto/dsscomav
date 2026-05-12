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

        if ($training->tipo_usuario_permitido === 'todos') {
            return true;
        }

        $permitidos = is_array($training->tipo_usuario_permitido) 
            ? $training->tipo_usuario_permitido 
            : json_decode($training->tipo_usuario_permitido, true) ?? [];

        return in_array($this->tipo_usuario, $permitidos);
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
}
