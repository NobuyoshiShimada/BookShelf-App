<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Review extends Model
{
    use HasFactory;

    /**
     * 複数代入（Mass Assignment）を許可する属性の配列
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'book_id',
        'rating',
        'comment',
    ];

    /**
     * このレビューデータをデータベースに投稿した親ユーザーへの多対1リレーション
     *
     * @return BelongsTo ユーザーモデルへの紐付け
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * レビュー対象となっている書籍への多対1リレーション
     *
     * @return BelongsTo 書籍モデルへの紐付け
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * このレビューに対して「いいね！」をしたユーザー一覧への多対多リレーション
     * （中間テーブル: `review_likes`）
     *
     * @return BelongsToMany ユーザーコレクションへの紐付け
     */
    public function likedByUsers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'review_likes')->withTimestamps();
    }

    /**
     * 指定されたユーザーが、このレビューに対してすでに「いいね！」を付与しているか判定
     *
     * @param  User|null  $user  判定対象のユーザーインスタンス（未ログイン時はnull）
     * @return bool すでにいいね！している場合は true、未いいね！または未ログイン時は false
     */
    public function isLikedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return $this->likedByUsers()->where('user_id', $user->id)->exists();
    }
}
