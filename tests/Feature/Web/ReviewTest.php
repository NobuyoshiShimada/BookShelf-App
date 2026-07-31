<?php

namespace Tests\Feature\Web;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
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

    public function test_未ログインユーザーのアクセス制限()
    {
        $review = Review::factory()->create(['book_id' => $this->book->id]);

        $this->assertGuest();
        // 認証が必要なページはすべてリダイレクト（302）されることをテスト
        $this->post(route('reviews.store', $this->book))->assertStatus(302);
        $this->post(route('reviews.like', $this->book))->assertStatus(302);
        $this->get(route('reviews.edit', $review))->assertStatus(302);
        $this->put(route('reviews.update', $review))->assertStatus(302);
        $this->delete(route('reviews.destroy', $review))->assertStatus(302);
    }

    public function test_ログインユーザーは新規レビュー登録処理ができる(): void
    {
        // テスト用レビューデータを1件作成
        $reviewData = [
            'rating' => 5,
            'comment' => '素晴らしい本でした。',
        ];

        // ログインして新規登録処理
        $response = $this->actingAs($this->user)
            ->post(route('reviews.store', $this->book), $reviewData);

        // データベースに登録されているかテスト
        $this->assertDatabaseHas('reviews', [
            'book_id' => $this->book->id,
            'user_id' => $this->user->id,
            'rating' => 5,
            'comment' => '素晴らしい本でした。',
        ]);

        // 登録後のリダイレクト先（books.show）、登録成功時のメッセージのテスト
        $response->assertRedirect(route('books.show', $this->book));
        $response->assertSessionHas('success', 'レビューを投稿しました。');
    }

    public function test_ログインユーザー本人が投稿したレビューは編集できる(): void
    {
        // テスト用に書籍と、ユーザーを作成
        $review = Review::factory()->create([
            'book_id' => $this->book->id,
            'user_id' => $this->user->id,
        ]);

        // ログインしてレビュー編集画面
        $response = $this->actingAs($this->user)
            ->get(route('reviews.edit', $review));

        $response->assertStatus(200);
        $response->assertViewIs('reviews.edit');
        $response->assertViewHas('review', $review);
    }

    public function test_ログインユーザーは他人が投稿したレビューの編集画面はアクセスできない(): void
    {
        // テスト用に書籍と、ユーザーを作成
        $otherUser = User::factory()->create();
        $review = Review::factory()->create([
            'book_id' => $this->book->id,
            'user_id' => $otherUser->id,
        ]);

        // ログインしてレビュー編集画面
        $response = $this->actingAs($this->user)
            ->get(route('reviews.edit', $review));

        // ステータス（403）をテスト
        $response->assertStatus(403);
    }

    public function test_ログインユーザー本人が投稿したレビューは更新処理ができる(): void
    {
        // テスト用にレビューを作成
        $review = Review::factory()->create([
            'book_id' => $this->book->id,
            'user_id' => $this->user->id,
            'rating' => 3,
            'comment' => '古いコメント',
        ]);

        // テスト用に更新情報
        $updatedData = [
            'rating' => 4,
            'comment' => '更新後のコメント',
        ];

        // ログインして更新処理
        $response = $this->actingAs($this->user)
            ->put(route('reviews.update', $review), $updatedData);

        // データベースへの登録確認
        $this->assertDatabaseHas('reviews', [
            'id' => $review->id,
            'rating' => 4,
            'comment' => '更新後のコメント',
        ]);

        // 更新後のリダイレクト先、更新成功時のメッセージのテスト
        $response->assertRedirect(route('books.show', $this->book));
        $response->assertSessionHas('success', 'レビューを更新しました。');
    }

    public function test_ログインユーザー本人が投稿したレビューは削除ができる(): void
    {
        // テスト用のレビュー作成
        $review = Review::factory()->create([
            'book_id' => $this->book->id,
            'user_id' => $this->user->id,
        ]);

        // ログインして削除
        $response = $this->actingAs($this->user)
            ->delete(route('reviews.destroy', $review));

        // レビューが削除されたか確認するテスト
        $this->assertDatabaseMissing('reviews', ['id' => $review->id]);

        // 削除後のリダイレクト先、削除成功時のメッセージのテスト
        $response->assertRedirect(route('books.show', $this->book));
        $response->assertSessionHas('success', 'レビューを削除しました。');
    }
}
