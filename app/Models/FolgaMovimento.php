<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolgaMovimento extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'user_id',
        'data',
        'tipo',
        'quantidade',
        'referencia_mes',
        'referencia_ano',
        'observacao',
        'created_by',
    ];

    protected $casts = [
        'data' => 'date',
        'quantidade' => 'integer',
        'referencia_mes' => 'integer',
        'referencia_ano' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
