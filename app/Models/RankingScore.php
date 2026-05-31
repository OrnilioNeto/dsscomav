<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RankingScore extends Model
{
    protected $table = 'ranking_scores';

    protected $fillable = [
        'user_id',
        'training_id',
        'content_id',
        'month_reference',
        'year_reference',
        'raw_score',
        'max_possible_score',
        'normalized_score',
        'calculated_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
