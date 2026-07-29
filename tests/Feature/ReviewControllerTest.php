<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewControllerTest extends TestCase
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

    // 未ログインユーザーのアクセス制限テスト
    public function test_guest_access(): void
    {
        $review = Review::factory()->create(['book_id' => $this->book->id]);

        $this->assertGuest();
        $this->post(route('reviews.store', $this->book))->assertRedirect();
        $this->post(route('reviews.like', $this->book))->assertRedirect();
        $this->get(route('reviews.edit', $review))->assertRedirect();
        $this->put(route('reviews.update', $review))->assertRedirect();
        $this->delete(route('reviews.destroy', $review))->assertRedirect();
    }

    // Indexのテスト
    public function test_store(): void
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

        // 登録後のリダイレクト先、登録成功時のメッセージのテスト
        $response->assertRedirect(route('books.show', $this->book));
        $response->assertSessionHas('success', 'レビューを投稿しました。');
    }

    // Editのテスト（投稿者本人）
    public function test_edit_owner(): void
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

    // Editのテスト（投稿者が他人）
    public function test_edit_non_owner(): void
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

        // ステータスをテスト
        $response->assertStatus(403);
    }

    // Updateのテスト
    public function test_update(): void
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

    // Deleteのテスト
    public function test_delete(): void
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

    // Toggleのテスト
    public function test_toggle(): void
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
