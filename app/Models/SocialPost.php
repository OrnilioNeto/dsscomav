<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialPost extends Model
{
    use HasFactory;

    protected $table = 'social_posts';

    protected $fillable = [
        'user_id',
        'photo_path',
        'caption',
        'location',
        'training_id',
        'training_score',
        'ranking_position',
    ];

    protected $casts = [
        'training_score' => 'double',
        'ranking_position' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function training()
    {
        return $this->belongsTo(Training::class);
    }

    public function likes()
    {
        return $this->hasMany(SocialLike::class, 'post_id');
    }

    public function comments()
    {
        return $this->hasMany(SocialComment::class, 'post_id')->orderBy('created_at', 'asc');
    }

    public function isLikedBy($userId): bool
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }

    public function isTrainingPost(): bool
    {
        return $this->training_id !== null;
    }

    public function getPhotoUrl(): ?string
    {
        if ($this->photo_path && file_exists(public_path("uploads/social/{$this->photo_path}"))) {
            return asset("uploads/social/{$this->photo_path}");
        }
        return null;
    }
}
