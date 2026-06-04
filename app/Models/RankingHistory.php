<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RankingHistory extends Model
{
    protected $table = 'ranking_histories';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'month_reference',
        'year_reference',
        'score',
        'created_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
