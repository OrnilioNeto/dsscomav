<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SplashContent extends Model
{
    protected $fillable = [
        'titulo',
        'texto_conteudo',
        'material_path',
        'material_tipo',
        'data_inicio',
        'data_fim',
        'status',
        'ordem'
    ];

    protected $casts = [
        'data_inicio' => 'date',
        'data_fim' => 'date',
    ];

    /**
     * Verifica se o conteúdo é uma imagem
     */
    public function isImage()
    {
        return $this->material_tipo === 'imagem';
    }

    /**
     * Verifica se o conteúdo é um PDF
     */
    public function isPdf()
    {
        return $this->material_tipo === 'pdf';
    }

    /**
     * Retorna a URL pública do material
     */
    public function getUrlAttribute()
    {
        return $this->material_path ? asset($this->material_path) : null;
    }
}