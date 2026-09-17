<?php

namespace App\Models;


use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SocialFollow extends Model
{
    use BelongsToTenant;
    protected $table = 'social_follows';

    protected $fillable = [
        'follower_id',
        'following_id',
    ];

    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_id');
    }

    public function following()
    {
        return $this->belongsTo(User::class, 'following_id');
    }
}
