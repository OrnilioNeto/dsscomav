<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RankingRule extends Model
{
    protected $table = 'ranking_rules';

    protected $fillable = [
        'criterion_id',
        'label',
        'min_value',
        'max_value',
        'points',
        'sort_order',
    ];

    public function criterion()
    {
        return $this->belongsTo(RankingCriterion::class, 'criterion_id');
    }
}
