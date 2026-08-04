<?php

namespace App\Models;

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
    ];

    // 計画を立てたユーザー
    public function users()
    {
        return $this->belongsTo(User::class);
    }

    // 対象の書籍
    public function books()
    {
        return $this->belongsTo(Book::class);
    }
}
