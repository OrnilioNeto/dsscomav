<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TenantModule extends Model
{
    protected $fillable = [
        'tenant_id',
        'module',
        'enabled',
        'enabled_at',
        'expires_at',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'enabled_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
