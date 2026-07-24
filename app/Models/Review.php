<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_id',
        'rating',
        'comment',
    ];

    // このレビューを投稿したユーザー(多対1)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // レビューされた本(多対1)
    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    // このレビューに「いいね」したユーザー一覧(多対多)
    // 中間テーブル: review_likes()
    // $review->likedByUsers()でアクセス可能
    public function likedByUsers()
    {
        return $this->belongsToMany(User::class, 'review_likes')->withTimestamps();
    }

    public function isLikedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->likedByUsers()->where('user_id', $user->id)->exists();
    }
}
