<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * 複数代入（Mass Assignment）を許可する属性の配列
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * 配列やJSONシリアライズ時に隠蔽（非表示）にする属性の配列
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * 適切なデータ型へ強制変換（キャスト）する属性の配列
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * このユーザー自身がシステムに登録した書籍一覧への1対多リレーション
     *
     * @return HasMany 書籍コレクションへの紐付け
     */
    public function books(): HasMany
    {
        return $this->hasMany(Book::class);
    }

    /**
     * このユーザー自身が投稿した全書籍レビュー一覧への1対多リレーション
     *
     * @return HasMany レビューコレクションへの紐付け
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * このユーザー自身が「お気に入り」に登録している書籍一覧への多対多リレーション
     * （中間テーブル: `favorites`）
     *
     * @return BelongsToMany 書籍コレクションへの紐付け
     */
    public function favoriteBooks(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'favorites')->withTimestamps();
    }

    /**
     * このユーザー自身が「いいね！」を付与した他ユーザーのレビュー一覧への多対多リレーション
     * （中間テーブル: `review_likes`）
     *
     * @return BelongsToMany レビューコレクションへの紐付け
     */
    public function likedReviews(): BelongsToMany
    {
        return $this->belongsToMany(Review::class, 'review_likes')->withTimestamps();
    }

    /**
     * このユーザー自身が策定した全書籍の読書計画一覧への1対多リレーション
     *
     * @return HasMany 読書計画コレクションへの紐付け
     */
    public function readingPlans(): HasMany
    {
        return $this->hasMany(ReadingPlan::class);
    }

    /**
     * 指定された書籍のお気に入り状態を反転（トグル）させ、処理結果のステータス文字列を返却
     *
     * @param int $bookId 対象の書籍ID
     * @return string 'attached'（追加時）または 'detached'（解除時）
     */
    public function toggleFavoriteBook(int $bookId): string
    {
        $result = collect($this->favoriteBooks()->toggle($bookId));

        return $result->get('detached', []) !== [] ? 'detached' : 'attached';
    }

     /**
     * 特定のレビューに対する「いいね！」状態を反転（トグル）処理
     *
     * @param int $reviewId 
     * @return void
     */
    public function toggleLikeReview(int $reviewId): void
    {
        $this->likedReviews()->toggle($reviewId);
    }
}
