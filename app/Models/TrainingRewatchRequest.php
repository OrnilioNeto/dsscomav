<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrainingRewatchRequest extends Model
{
    use HasFactory;

    protected $table = 'training_rewatch_requests';

    protected $fillable = [
        'user_id',
        'training_id',
        'justificativa',
        'authorized_by',
        'certificate_anterior_id',
        'certificate_novo_id',
        'status',
    ];

    protected $casts = [
        'justificativa' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function authorizer()
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    public function certificateAnterior()
    {
        return $this->belongsTo(Certificate::class, 'certificate_anterior_id');
    }

    public function certificateNovo()
    {
        return $this->belongsTo(Certificate::class, 'certificate_novo_id');
    }
}
