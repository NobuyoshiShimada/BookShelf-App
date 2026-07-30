<?php

namespace Tests\Feature;
use App\Models\Book;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FavoriteControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        // テスト用のユーザーを作成
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // 未ログインユーザーのアクセス制限テスト
    public function test_guest_access(): void
    {
        // テスト用の書籍を1冊作成
        $book = Book::factory()->create();

        $this->assertGuest();

        $this->get(route('favorites.index'))->assertRedirect();
        $this->post(route('favorites.toggle', $book))->assertRedirect();
    }

    // Indexのテスト
    public function test_index(): void
    {
        // テスト用の書籍を1冊作成
        $book = Book::factory()->create();

        // ユーザーとfavoriteBooksを紐付ける
        $this->user->favoriteBooks()->attach($book->id);

        // ログインして、お気に入り一覧
        $response = $this->actingAs($this->user)
        ->get(route('favorites.index'));

        // ステータス、画面、書籍情報を取得できるかテスト
        $response->assertStatus(200);
        $response->assertViewIs('favorites.index');
        $response->assertViewHas('books');
    }

    // Toggleのテスト
    public function test_toggle(): void
    {
        // テスト用の書籍を1冊作成
        $book = Book::factory()->create();

        // お気に入りボタン1回目の押下：お気に入りに追加
        $response = $this->actingAs($this->user)
            ->from(route('books.show', $book))
            ->post(route('favorites.toggle', $book));

        // 中間テーブルのデータベースにお気に入り（book->id,user->id）が登録される
        $this->assertDatabaseHas('favorites', [
            'user_id' => $this->user->id,
            'book_id' => $book->id,
        ]);

        // 登録後のリダイレクト先、登録成功時にメッセージのテスト
        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success', 'お気に入りを追加しました。');

        // お気に入りボタン2回目の押下：お気に入りを解除
        $response = $this->actingAs($this->user)
            ->from(route('books.show', $book))
            ->post(route('favorites.toggle', $book));

        // 中間テーブルのデータベースからお気に入り（book->id,user->id）が削除される
        $this->assertDatabaseMissing('favorites', [
            'user_id' => $this->user->id,
            'book_id' => $book->id,
        ]);

        // リダイレクト先のテスト
        $response->assertRedirect(route('books.show', $book));
    }
}
