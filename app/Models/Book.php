<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    use HasFactory;

    /**
     * 複数代入（Mass Assignment）を許可する属性の配列
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'author',
        'isbn',
        'published_date',
        'description',
        'image_url',
    ];

    /**
     * 適切なデータ型へ強制変換（キャスト）する属性の配列
     *
     * @var array<string, string>
     */
    protected $casts = [
        'published_date' => 'date',
    ];

    /**
     * この書籍データをデータベースに登録した親ユーザーへの多対1リレーション
     *
     * @return BelongsTo ユーザーモデルへの紐付け
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この書籍に対して投稿された全ユーザーからのレビュー一覧への1対多リレーション
     *
     * @return HasMany レビューコレクションへの紐付け
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * この書籍に割り当てられているジャンル一覧への多対多リレーション
     * （中間テーブル: `book_genre`）
     *
     * @return BelongsToMany ジャンルコレクションへの紐付け
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'book_genre')->withTimestamps();
    }

    /**
     * この書籍をお気に入り登録しているユーザー一覧への多対多リレーション
     * （中間テーブル: `favorites`）
     *
     * @return BelongsToMany ユーザーコレクションへの紐付け
     */
    public function favoriteBooks(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }

    /**
     * この書籍を対象として作成された全ユーザーの読書計画一覧への1対多リレーション
     *
     * @return HasMany 読書計画コレクションへの紐付け
     */
    public function readingPlans(): HasMany
    {
        return $this->hasMany(ReadingPlan::class);
    }
}
