<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RankingRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'criterion_id',
        'label',
        'min_value',
        'max_value',
        'points',
        'sort_order',
    ];

    protected $casts = [
        'min_value' => 'float',
        'max_value' => 'float',
        'points' => 'integer',
        'sort_order' => 'integer',
    ];

    public function criterion()
    {
        return $this->belongsTo(RankingCriterion::class);
    }
}