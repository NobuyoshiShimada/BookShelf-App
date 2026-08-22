<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;

    // BookモデルがUserモデルに属しているかテスト（1対1）
    public function test_book_belongs_to_user(): void
    {
        // テスト用の書籍を1冊作成（Factoryにより裏でuserも1人作成されて紐付く）
        $book = Book::factory()->create();
        // $book->userがUserクラスのインスタンスであるかテスト
        $this->assertInstanceOf(User::class, $book->user);
    }

    // Bookモデルが複数のReviewを保持できるかテスト（1対多）
    public function test_has_many_reviews(): void
    {
        // テスト用の書籍を1冊作成
        $book = Book::factory()->create();
        // この本に紐付くレビューを2件作成
        Review::factory()->count(2)->create(['book_id' => $book->id]);

        // $book->reviewsがEloquentのコレクションであり、件数が2件であることをテスト
        $this->assertInstanceOf(Collection::class, $book->reviews);
        $this->assertCount(2, $book->reviews);
    }

    // Bookモデルが複数のGenreを保持できるかテスト（多対多）
    public function test_book_belongs_to_many_genres(): void
    {
        // テスト用の書籍を1冊作成
        $book = Book::factory()->create();
        // テスト用のジャンルを3件作成
        $genres = Genre::factory()->count(3)->create();

        // 多対多の中間テーブル(book_genre)にジャンルを紐付け
        $book->genres()->attach($genres->pluck('id'));

        // 紐付けた3件のジャンルが正しく取得できるかテスト
        $this->assertCount(3, $book->genres);
        $this->assertInstanceOf(Genre::class, $book->genres->first());
    }

    // キーワード検索とジャンルフィルタが正しく動作する
    public function test_scope_filter_and_sort_keyword_genre(): void
    {
        $genrePhp = Genre::factory()->create(['name' => 'PHP']);
        $genreGo = Genre::factory()->create(['name' => 'Go']);

        $book1 = Book::factory()->create(['title' => 'Laravel実践入門', 'author' => '山田太郎']);
        $book1->genres()->attach($genrePhp->id);

        $book2 = Book::factory()->create(['title' => 'Go言語独習', 'author' => '鈴木次郎']);
        $book2->genres()->attach($genreGo->id);

        // ① キーワード（タイトル）検索の検証
        $results = Book::filterAndSort(['keyword' => 'Laravel'])->get();
        $this->assertCount(1, $results);
        $this->assertEquals('Laravel実践入門', $results->first()->title);

        // ② キーワード（著者名）検索の検証
        $results = Book::filterAndSort(['keyword' => '鈴木'])->get();
        $this->assertCount(1, $results);
        $this->assertEquals('Go言語独習', $results->first()->title);

        // ③ ジャンルフィルタの検証
        $results = Book::filterAndSort(['genre' => $genrePhp->id])->get();
        $this->assertCount(1, $results);
        $this->assertEquals('Laravel実践入門', $results->first()->title);
    }

    // 全ソート条件の並び替えが正確に適用される
    public function test_scope_filter_and_sort_sort(): void
    {
        // 時間差を設けて書籍を作成
        $bookOld = Book::factory()->create(['title' => 'A_古い本', 'created_at' => Carbon::now()->subDays(2)]);
        $bookNew = Book::factory()->create(['title' => 'C_新しい本', 'created_at' => Carbon::now()]);

        // レビュー評価に差をつける (bookOld: 星5, bookNew: 星3)
        Review::factory()->create(['book_id' => $bookOld->id, 'rating' => 5]);
        Review::factory()->create(['book_id' => $bookNew->id, 'rating' => 3]);

        // ① newest (最新順) ➔ デフォルト挙動の検証
        $results = Book::filterAndSort(['sort' => 'newest'])->get();
        $this->assertEquals('C_新しい本', $results->get(0)->title);
        $this->assertEquals('A_古い本', $results->get(1)->title);

        // ② oldest (古い順) の検証
        $results = Book::filterAndSort(['sort' => 'oldest'])->get();
        $this->assertEquals('A_古い本', $results->get(0)->title);
        $this->assertEquals('C_新しい本', $results->get(1)->title);

        // ③ rating (評価順) の検証
        $results = Book::filterAndSort(['sort' => 'rating'])->get();
        $this->assertEquals('A_古い本', $results->get(0)->title); // 星5
        $this->assertEquals('C_新しい本', $results->get(1)->title); // 星3

        // ④ title (タイトル昇順) の検証
        $results = Book::filterAndSort(['sort' => 'title'])->get();
        $this->assertEquals('A_古い本', $results->get(0)->title); // Aから始まる
        $this->assertEquals('C_新しい本', $results->get(1)->title); // Cから始まる
    }

    /**
     * createWithGenres メソッドの正常系テスト
     */
    public function test_create_with_genres(): void
    {
        $user = User::factory()->create();
        $genre1 = Genre::factory()->create();
        $genre2 = Genre::factory()->create();

        // コントローラーから移譲された作成ロジックの実行
        $book = Book::createWithGenres([
            'user_id' => $user->id,
            'title' => 'テスト駆動開発',
            'author' => 'ケント・ベック',
            'isbn' => '9784274217883',
            'published_date' => '2015-10-01',
            'description' => 'テストのバイブル',
            'image_url' => null,
        ], [$genre1->id, $genre2->id]);

        $this->assertInstanceOf(Book::class, $book);
        $this->assertDatabaseHas('books', ['title' => 'テスト駆動開発']);

        // 中間テーブルを介してジャンルが2件正しく紐付いているか検証
        $this->assertCount(2, $book->genres);
    }

    /**
     * updateWithGenres メソッドの正常系テスト
     */
    public function test_update_with_genres(): void
    {
        $book = Book::factory()->create(['title' => '古いタイトル']);
        $genre = Genre::factory()->create();

        // コントローラーから移譲された一括更新ロジックの実行
        $status = $book->updateWithGenres([
            'title' => '新しいタイトル',
        ], [$genre->id]);

        $this->assertTrue($status);
        $this->assertDatabaseHas('books', ['id' => $book->id, 'title' => '新しいタイトル']);
        $this->assertTrue($book->genres()->where('genre_id', $genre->id)->exists());
    }

    /**
     * purgeFully メソッドの完全連動削除テスト
     */
    public function test_purge_fully(): void
    {
        $book = Book::factory()->hasReviews(3)->create();
        $genre = Genre::factory()->create();
        $book->genres()->attach($genre->id);

        // コントローラーから移譲された完全抹消ロジックの実行
        $book->purgeFully();

        // 親データ、中間テーブル、子孫データが連動して消えているかアサート
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        $this->assertDatabaseCount('book_genre', 0);
        $this->assertDatabaseCount('reviews', 0);
    }
}
