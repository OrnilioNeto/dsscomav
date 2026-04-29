<?php

namespace App\Models;

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
        'cnh',
        'categoria_cnh',
        'validade_cnh',
        'setor',
        'cargo',
        'empresa',
        'responsavel',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'data_nascimento' => 'date',
        'validade_cnh' => 'date',
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
}
