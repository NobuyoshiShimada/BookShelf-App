<?php

namespace Tests\Feature\Web;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LikeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Book $book;

    // 認証用ユーザー1人とテスト用の1冊書籍作成
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->book = Book::factory()->create();
    }

    public function test_ログインユーザーはレビューのいいねの登録・解除ができる(): void
    {
        // テスト用のレビュー作成
        $review = Review::factory()->create([
            'book_id' => $this->book->id,
            'user_id' => User::factory()->create()->id,
        ]);

        // ログインして、いいねの追加（1回目の押下）
        $response = $this->actingAs($this->user)
            ->from(route('books.show', $this->book))
            ->post(route('reviews.like', ['book' => $review->id]));

        // データベースに登録されたか確認のテスト
        $this->assertDatabaseHas('review_likes', [
            'user_id' => $this->user->id,
            'review_id' => $review->id,
        ]);
        // リダイレクト先のテスト
        $response->assertRedirect(route('books.show', $this->book));

        // いいねの解除（2回目の押下）
        $response = $this->actingAs($this->user)
            ->from(route('books.show', $this->book))
            ->post(route('reviews.like', ['book' => $review->id]));

        // データベースから削除されたか確認のテスト
        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $this->user->id,
            'review_id' => $review->id,
        ]);

        // リダイレクト先のテスト
        $response->assertRedirect(route('books.show', $this->book));
    }
}
