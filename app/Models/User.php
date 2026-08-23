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
        /**
     * 指定された書籍のお気に入り状態を反転（トグル）させ、処理結果のステータス文字列を返却
     *
     * @param  int  $bookId  対象の書籍ID
     * @return string 'attached'（追加時）または 'detached'（解除時）
     */
    public function toggleFavoriteBook(int $bookId): string
    {
        $result = collect($this->favoriteBooks()->toggle($bookId));

        return $result->get('detached', []) !== [] ? 'detached' : 'attached';
    }

    /**
     * 特定のレビューに対する「いいね！」状態を反転（トグル）処理
     */
    public function toggleLikeReview(int $reviewId): void
    {
        $this->likedReviews()->toggle($reviewId);
    }
}

