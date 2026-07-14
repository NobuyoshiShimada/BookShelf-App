<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // 自分が登録した本の一覧(1対多)
    public function books()
    {
        return $this->hasMany(Book::class);
    }

    // 自分が投稿したレビューの一覧(1対多)
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // 自分が「お気に入り」している本の一覧(多対多)
    // 中間テーブル: favorites
    // $user->favoriteBooks()でアクセス可能
    public function favoriteBooks()
    {
        return $this->belongsToMany(Book::class, 'favorites')->withTimestamps();
    }

    // 自分が「いいね」したレビューの一覧(多対多)
    // 中間テーブル: review_likes
    // $user->likedReviews()でアクセス可能
    public function likedReviews()
    {
        return $this->belongsToMany(Review::class, 'review_likes')->withTimestamps();
    }
}
