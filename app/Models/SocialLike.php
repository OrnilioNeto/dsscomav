<?php

namespace App\Models;


use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class SocialLike extends Model
{
    use BelongsToTenant;
    protected $table = 'social_likes';

    protected $fillable = [
        'user_id',
        'post_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        return $this->belongsTo(SocialPost::class, 'post_id');
    }
}
