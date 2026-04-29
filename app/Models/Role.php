<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'descricao',
    ];

    public $timestamps = true;

    // Relacionamentos
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
