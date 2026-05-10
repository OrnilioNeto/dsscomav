<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingMaterial extends Model
{
    use HasFactory;

    protected $table = 'training_materials';

    protected $fillable = [
        'training_id',
        'nome',
        'descricao',
        'arquivo',
        'tipo_arquivo',
        'tamanho',
        'ordem',
    ];

    protected $casts = [
        'tamanho' => 'integer',
        'ordem' => 'integer',
    ];

    // Relacionamentos
    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    // Métodos auxiliares
    public function getTamanhoFormatado()
    {
        $bytes = $this->tamanho;
        $unidades = ['B', 'KB', 'MB', 'GB'];
        $tamanho = $bytes;

        foreach ($unidades as $unidade) {
            if ($tamanho < 1024) {
                return round($tamanho, 2) . ' ' . $unidade;
            }
            $tamanho /= 1024;
        }

        return round($tamanho, 2) . ' TB';
    }

    public function getIcone()
    {
        $extensoes = explode('.', $this->arquivo);
        $extensao = strtolower(end($extensoes));

        $icones = [
            'pdf' => 'fa-file-pdf text-red-600',
            'doc' => 'fa-file-word text-blue-600',
            'docx' => 'fa-file-word text-blue-600',
            'xls' => 'fa-file-excel text-green-600',
            'xlsx' => 'fa-file-excel text-green-600',
            'jpg' => 'fa-file-image text-purple-600',
            'jpeg' => 'fa-file-image text-purple-600',
            'png' => 'fa-file-image text-purple-600',
            'gif' => 'fa-file-image text-purple-600',
            'zip' => 'fa-file-archive text-yellow-600',
            'rar' => 'fa-file-archive text-yellow-600',
            'txt' => 'fa-file-text text-gray-600',
        ];

        return $icones[$extensao] ?? 'fa-file text-gray-600';
    }
}
