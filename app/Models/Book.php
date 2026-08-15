<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Builder;
use Psy\TabCompletion\Matcher\FunctionsMatcher;

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

    /**
     * ジャンル紐付けを内包した安全な一括作成処理
     *
     * @param array<string, mixed> $attributes 書籍の属性配列
     * @param array<int, int> $genreIds 紐付けるジャンルIDの配列
     * @return self 生成された書籍モデルインスタンス
     */
    public static function createWithGenres(array $attributes, array $genreIds): self
    {
        return DB::transaction(function () use ($attributes, $genreIds) {
            $book = self::create($attributes);
            collect($genreIds)->whenNotEmpty(fn ($ids) => $book->genres()->sync($ids));
            return $book;
        });
    }

    /**
     * ジャンル再同期を内包した安全な一括更新処理
     *
     * @param array<string, mixed> $attributes 更新する属性配列
     * @param array<int, int> $genreIds 再同期するジャンルIDの配列
     * @return bool 更新成否のステータス
     */
    public function updateWithGenres(array $attributes, ?array $genreIds): bool
    {
        return DB::transaction(function () use ($attributes, $genreIds) {
            $updated = $this->update($attributes);
            if ($genreIds !== null) {
                $this->genres()->sync($genreIds);
            }
            return $updated;
        });
    }

    /**
     * データベースから書籍と紐づくすべての子孫データをトランザクション内で完全抹消
     *
     * @return void
     * @throws \Throwable トランザクション内でエラーが発生した場合
     */
    public Function purgeFully(): void
    {
        DB::transaction(function () {
            $this->genres()->sync([]);
            $this->reviews()->delete();
            $this->delete();
        });
    }


}
