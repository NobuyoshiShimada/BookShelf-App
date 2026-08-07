<?php

namespace App\Models;

use App\Enums\ReadingPlanStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReadingPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'book_id',
        'target_date',
        'status',
        'completed_at',
    ];

    // カラムとEnumオブジェクトのキャストの紐付ける
    protected $casts = [
        'status' => ReadingPlanStatus::class,
        'target_date' => 'date',
        'completed_at' => 'date',
    ];

    // 計画を立てたユーザー
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // 対象の書籍
    public function book()
    {
        return $this->belongsTo(Book::class);
    }
}
