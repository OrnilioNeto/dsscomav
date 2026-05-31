<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RankingMonthlyScore extends Model
{
    protected $table = 'ranking_monthly_scores';

    protected $fillable = [
        'user_id',
        'month_reference',
        'year_reference',
        'average_score',
        'position',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
