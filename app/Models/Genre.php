<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Genre extends Model
{
    use HasFactory;

    /**
     * 複数代入（Mass Assignment）を許可する属性の配列
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
    ];

    /**
     * このジャンルに分類・紐付けられている書籍一覧への多対多リレーション
     * （中間テーブル: `book_genre`）
     *
     * @return BelongsToMany 書籍コレクションへの紐付け
     */
    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'book_genre')->withTimestamps();
    }
}
