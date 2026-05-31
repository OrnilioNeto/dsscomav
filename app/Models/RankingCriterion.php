<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RankingCriterion extends Model
{
    protected $table = 'ranking_criteria';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'sort_order',
    ];

    public function rules()
    {
        return $this->hasMany(RankingRule::class, 'criterion_id');
    }
}
