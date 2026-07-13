<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'author',
        'isbn',
        'published_date',
        'description',
        'image_url',
    ];

    // この本を登録したユーザー(多対1)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // この本に投稿されたレビュー(1対多)
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    // この本に紐づいているジャンル一覧(多対多)
    // 中間テーブル: book_genre
    // $book->genres()でアクセス可能
    public function genres()
    {
        return $this->belongsToMany(Genre::class, 'book_genre')->withTimestamps();
    }

    // この本をお気に入り登録しているユーザー一覧(多対多)
    // 中間テーブル: favorites
    // $book->favoritedByUsers()でアクセス可能
    public function favoritedByUsers()
    {
        return $this->belongsToMany(User::class, 'favorites')->withTimestamps();
    }
}
