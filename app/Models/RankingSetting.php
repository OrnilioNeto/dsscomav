<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RankingSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'is_active',
        'default_period',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}