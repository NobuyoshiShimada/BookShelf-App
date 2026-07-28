<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\TestCase;

class BookTest extends TestCase
{
    use RefreshDatabase;
    /**
     * A basic unit test example.
     */
    // ユーザーが複数の書籍を登録できるかテスト（1対多）
    public function test_user_user_has_many_books(): void
    {
        // テスト用ユーザーを1人作成
        $user = User::factory()->create();
        // このユーザーが登録した本を2冊ダミー作成
        Book::factory()->count(2)->create(['user_id' => $user->id]);

        // $user->bookがEloquentのコレクションで、件数が2件をテスト
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->books);
        $this->assertCount(2, $user->books);
    }

    // ユーザーが複数のレビューを投稿できるかテスト（1対多）
    public function test_user_has_many_reviews(): void
    {
        // テスト用ユーザーを1人作成
        $user = User::factory()->create();

        // このユーザーが投稿したレビューを3件ダミー作成
        Review::factory()->count(3)->create(['user_id' => $user->id]);

        // $user->reviewがEloquentのコレクションで、件数が3件をテスト
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $user->reviews);
        $this->assertCount(3, $user->reviews);
    }

    // ユーザーが書籍をお気に入り登録できるかテスト（多対多）
    public function test_user_belongs_to_many_favorite_books(): void
    {
        // テスト用ユーザーを1人作成
        $user = User::factory()->create();
        // テスト用書籍を2冊作成
        $books = Book::factory()->count(2)->create();

        // 中間テーブル「favorites」を介してお気に入り登録（紐付け）
        $user->favoriteBooks()->attach($books->plunk('id'));

        // お気に入りした本が2冊正しく引き抜けるかテスト
        $this->assertCount(2, $user->favoriteBooks);
        $this->assertInstanceOf(Book::class, $user->favoriteBooks->first());
    }

    // ユーザーがレビューにいいねできるかテスト（多対多）
    public function test_user_belongs_to_many_liked_reviews(): void
    {
        // テスト用ユーザーを1人作成
        $user = User::factory()->create();
        // テスト用レビューを2件作成
        $reviews = Review::factory()->count(2)->create();

        // 中間テーブル「review_likes」を介していいね（紐付け）
        $user->likedReviews()->attach($reviews->plunk('id'));

        // いいねしたレビューが2件正しく引き抜けるかテスト
        $this->assertCount(2, $user->likedReviews);
        $this->assertInstanceOf(Review::class, $user->likedReviews->first());
    }
}
