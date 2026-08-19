<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use RefreshDatabase;

    /**
     * 
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
        $this->assertInstanceOf(Collection::class, $genre->books);
        $this->assertCount(2, $genre->books);
        $this->assertInstanceOf(Book::class, $genre->books->first());
    }

    /**
     * purgeFully メソッドの連動削除テスト
     *
     *
     * ジャンルを完全抹消した際、中間テーブル（book_genre）の紐付けが
     * トランザクション内で綺麗にクリアされ、書籍本体は巻き添えで消えないことを厳格に保護します。
     */
    public function test_purgeFullyメソッドで中間テーブルの紐付けが安全にクリアされジャンルが削除される(): void
    {
        $genre = Genre::factory()->create();
        $book  = Book::factory()->create();

        // 中間テーブルへの紐付け
        $genre->books()->attach($book->id);

        // 💡 コントローラーから移譲された完全抹消ロジックの実行
        $genre->purgeFully();

        // 1. ジャンル自体が消えていることを検証
        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);

        // 2. 中間テーブルのレコードが完全にお掃除されたかアサート
        $this->assertDatabaseCount('book_genre', 0);

        // 3. 【重要】書籍本体は消えてはいけない（独立して存在し続ける）ことを検証
        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }
}
