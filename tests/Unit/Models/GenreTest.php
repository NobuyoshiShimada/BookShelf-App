<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic unit test example.
     */
    // ユーザーが複数の書籍を登録できるかテスト（多対多）
    public function test_genre_belongs_to_many_books(): void
    {
        // テスト用のジャンルを1件作成
        $genre = Genre::factory()->create();
        // テスト用の書籍を2冊作成
        $books = Book::factory()->count(2)->create();

        // 多対多の中間テーブル（book_genre）を介して、ジャンルに書籍を紐付け
        $genre->books()->attach($books->pluck('id'));

        // 紐付けた2冊の書籍が正しくコレクションとして引き抜けるかテスト
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $genre->books);
        $this->assertCount(2, $genre->books);
        $this->assertInstanceOf(Book::class, $genre->books->first());
    }
}
