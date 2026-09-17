<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FolgaLog extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'user_id',
        'acao',
        'tabela',
        'registro_id',
        'dados_antes',
        'dados_depois',
        'observacao',
        'created_by',
    ];

    protected $casts = [
        'dados_antes' => 'json',
        'dados_depois' => 'json',
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
