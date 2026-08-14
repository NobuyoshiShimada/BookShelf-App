<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdvancedBookCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $ownerUser;

    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->ownerUser = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    public function test_認証無しで書籍一覧を取得(): void
    {
        Book::factory()->count(2)->create([
            'user_id' => $this->ownerUser->id,
        ]);

        Book::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->getJson('/api/v1/books');

        $response->assertStatus(200)->assertJsonCount(3, 'data');
    }

    public function test_認証無しでの新規書籍登録は401で拒否(): void
    {
        $genre = Genre::factory()->create();

        $bookData = [
            'title' => 'テスト駆動',
            'author' => 'テスト太郎',
            'isbn' => '5678901234567',
            'published_date' => '2013-10-14',
            'description' => 'テストの説明書です。',
            'image_url' => 'https://example.com',
            'genres' => [$genre->id],
        ];

        $response = $this->postJson('/api/v1/books', $bookData, ['Accept' => 'application/json']);
        $response->assertStatus(401);
    }

    public function test_sanctum認証済みであれば新規書籍登録できる(): void
    {
        $genre = Genre::factory()->create();

        Sanctum::actingAs($this->ownerUser);

        $bookData = [
            'title' => 'テスト駆動',
            'author' => 'テスト太郎',
            'isbn' => '5678901234567',
            'published_date' => '2013-10-14',
            'description' => 'https://example.com',
            'genres' => [$genre->id],
        ];

        $response = $this->postJson('/api/v1/books', $bookData);

        $response->assertStatus(201);

        // データベースにuser_idに認証された本人のいっで保存されている
        $this->assertDatabaseHas('books', [
            'title' => 'テスト駆動',
            'user_id' => $this->ownerUser->id,
        ]);
    }

    public function test_認証なしで書籍の詳細を取得できる(): void
    {
        $book = Book::factory()->create(['user_id' => $this->ownerUser->id]);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $book->id);
    }

    public function test_書籍登録者本人でsanctum認証で更新できる(): void
    {
        $book = Book::factory()->create([
            'user_id' => $this->ownerUser->id,
            'title' => '古いタイトル',
        ]);

        $genre = Genre::factory()->create();

        $updateData = [
            'title' => '新しいタイトル',
            'author' => '新しい著者',
            'isbn' => $book->isbn,
            'published_date' => '2026-07-20',
            'description' => '新しい説明文',
            'image_url' => 'https://test_example.com',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($this->ownerUser);
        $response = $this->putJson("/api/v1/books/{$book->id}", $updateData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '新しいタイトル',
        ]);
    }

    public function test_他ユーザーが登録した書籍の更新は403で拒否(): void
    {
        $book = Book::factory()->create([
            'user_id' => $this->ownerUser->id,
        ]);
        $genre = Genre::factory()->create();

        $updateData = [
            'title' => '勝手に書き換え',
            'author' => '適当な著者',
            'isbn' => '2222222222222',
            'published_date' => '2000-12-12',
            'description' => '適当な説明',
            'image_url' => 'https://example_example.com',
            'genres' => [$genre->id],
        ];

        Sanctum::actingAs($this->otherUser);
        $response = $this->putJson("/api/v1/books/{$book->id}", $updateData);

        $response->assertStatus(403)->assertJsonFragment([
            'message' => '自分が登録した書籍情報のみ更新できます。',
        ]);
    }

    public function test_書籍登録者本人はsanctum認証で削除できる(): void
    {
        $book = Book::factory()->create([
            'user_id' => $this->ownerUser->id,
        ]);

        Sanctum::actingAs($this->ownerUser);
        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    public function test_他ユーザーが登録した書籍の削除は403で拒否(): void
    {
        $book = Book::factory()->create([
            'user_id' => $this->ownerUser->id,
        ]);

        Sanctum::actingAs($this->otherUser);
        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(403)->assertJsonFragment([
            'message' => '自分が登録した書籍のみ削除できます。',
        ]);

        $this->assertDatabaseHas('books', ['id' => $book->id]);
    }

    public function test_不正なデータでの初期登録は422エラー(): void
    {
        Sanctum::actingAs($this->ownerUser);

        // タイトルを空、出版日を不正な文字列にして送信
        $invalidData = [
            'title' => '',
            'author' => 'テスト太郎',
            'isbn' => '5678901234567',
            'published_date' => '不正な日付形式',
        ];

        $response = $this->postJson('/api/v1/books', $invalidData);

        // 💡 期待値: 422 が返り、エラーメッセージが含まれていること
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'published_date']);
    }
}
