<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic unit test example.
     */
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
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $book->reviews);
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
}
