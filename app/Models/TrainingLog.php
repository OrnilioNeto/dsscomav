<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingLog extends Model
{
    use HasFactory;

    protected $table = 'training_logs';

    public $timestamps = false;

    protected $fillable = [
        'training_id',
        'user_id',
        'evento',
        'detalhe',
        'ip',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function training()
    {
        return $this->belongsTo(Training::class, 'training_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * Registra um evento de log de treinamento de forma segura (sem quebrar o fluxo).
     */
    public static function registrar(int $trainingId, ?int $userId, string $evento, ?string $detalhe = null): void
    {
        try {
            self::create([
                'training_id' => $trainingId,
                'user_id' => $userId,
                'evento' => $evento,
                'detalhe' => $detalhe,
                'ip' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 500),
                'created_at' => now(),
            ]);
        } catch (\Throwable $e) {
            \Log::warning('Falha ao registrar log de treinamento: ' . $e->getMessage());
        }
    }
}