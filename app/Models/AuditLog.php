<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use BelongsToTenant;

    protected $table = 'audit_logs';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'event',
        'module',
        'auditable_type',
        'auditable_id',
        'description',
        'old_values',
        'new_values',
        'ip',
        'user_agent',
        'route',
        'method',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getEventoLabelAttribute(): string
    {
        return match ($this->event) {
            'created' => 'Criação',
            'updated' => 'Alteração',
            'deleted' => 'Exclusão',
            'login' => 'Login',
            'login_failed' => 'Falha de login',
            'login_blocked' => 'Login bloqueado',
            'logout' => 'Logout',
            'downloaded' => 'Download',
            'exported' => 'Exportação',
            'accessed' => 'Acesso',
            default => ucfirst((string) $this->event),
        };
    }

    public function getModuloLabelAttribute(): string
    {
        return match ($this->module) {
            'users' => 'Usuários',
            'trainings' => 'Treinamentos',
            'certificates' => 'Certificados',
            'rankings' => 'Ranking',
            'splash' => 'Splash',
            'social' => 'Rede Social',
            'epi' => 'EPI',
            'projeto_pedagogico' => 'Projeto Pedagógico',
            'folgas' => 'Folgas',
            'permissions' => 'Permissões',
            'plataforma' => 'Plataforma',
            'auth' => 'Autenticação',
            'auditoria' => 'Auditoria',
            default => $this->module ? ucfirst((string) $this->module) : '—',
        };
    }
}
