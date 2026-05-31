<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RankingSetting extends Model
{
    protected $table = 'ranking_settings';

    protected $fillable = [
        'is_active',
        'default_period',
    ];
}
