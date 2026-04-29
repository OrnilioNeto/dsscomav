<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'training_id',
        'codigo_certificado',
        'data_emissao',
        'caminho_arquivo',
        'valido',
    ];

    protected $casts = [
        'data_emissao' => 'datetime',
        'valido' => 'boolean',
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

    // Métodos auxiliares
    public function generateCodigoUnico()
    {
        return strtoupper(
            substr(md5($this->user_id . $this->training_id . time()), 0, 12)
        );
    }
}
