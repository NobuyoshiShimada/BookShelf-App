<?php

namespace App\Models;

use App\Enums\ReadingPlanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ReadingPlan extends Model
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
        'target_date',
        'status',
        'completed_at',
    ];

    /**
     * 適切なデータ型やEnumオブジェクトへ強制変換（キャスト）する属性の配列
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => ReadingPlanStatus::class,
        'target_date' => 'date',
        'completed_at' => 'date',
    ];

    /**
     * この読書計画を策定した親ユーザーへの多対1リレーション
     *
     * @return BelongsTo ユーザーモデルへの紐付け
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * この読書計画の対象となっている書籍への多対1リレーション
     *
     * @return BelongsTo 書籍モデルへの紐付け
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

}
