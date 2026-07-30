<?php

namespace Tests\Feature\Api\V1;

use App\Models\Book;
use App\Models\User;
use App\Models\Genre;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class BookControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $guestUser;
    private const GUEST_USER_ID = 999;

    protected function setUp(): void
    {
        parent::setUp();

        $this->guestUser = User::factory()->create([
            'id' => self::GUEST_USER_ID,
        ]);
    }

    public function 認証なしで書籍一覧を取得できる(): void
    {
        Book::factory()->count(2)->create([
            'user_id' => self::GUEST_USER_ID
        ]);

        Book::factory()->create([
            'user_id' => User::factory()->create()->id,
        ]);

        $response = $this->get('/api/v1/books');

        $response->assertStatus(200)
        ->assertJsonCount(3, 'data');
    }

    public function 認証無しで新規書籍を登録でき、自動的にゲストIDが割り当てられる()
    {
        $bookData = [
            'title' => 'テスト駆動開発',
            'author' => 'Kent Beck',
            'isbn' => '9784873116112',
            'published_date' => '2013-10-14',
            'description' => 'テストの解説書です。',
            'image_url' => 'https://example.com',
        ];

        // 認証なし（ゲスト）でPOSTリクエスト
        $response = $this->postJson('/api/v1/books', $bookData);

        $response->assertStatus(201)
            ->assertJsonPath('data.title', 'テスト駆動開発');

        // データベースを検証：user_id が強制的に 999 で保存されていること
        $this->assertDatabaseHas('books', [
            'title' => 'テスト駆動開発',
            'user_id' => self::GUEST_USER_ID,
        ]);
    }

    /** @test */
    public function 認証なしで書籍の詳細を取得できる()
    {
        $book = Book::factory()->create(['user_id' => self::GUEST_USER_ID]);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $book->id);
    }

    /** @test */
    public function ゲストが登録した書籍を認証なしで更新できる()
    {
        $book = Book::factory()->create([
            'user_id' => self::GUEST_USER_ID,
            'title' => '古いタイトル'
        ]);

        $genre = Genre::factory()->create();

        $updateData = [
            'title' => '新しいタイトル',
            'author' => '新しい著者',
            'isbn' => '1111111111111',
            'published_date' => '2026-07-20',
            'description' => '新しい説明文',
            'image_url' => 'https://test_example.com',
            'genres' => [$genre->id],
        ];

        $response = $this->putJson("/api/v1/books/{$book->id}", $updateData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '新しいタイトル',
        ]);
    }

    /** @test */
    public function 他の通常ユーザーが登録した書籍の更新は拒否される()
    {
        // 他のユーザー（ID: 1等）が登録した本を作成
        $otherUser = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $otherUser->id]);

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

        $response = $this->putJson("/api/v1/books/{$book->id}", $updateData);

        // ステータス403が返ることをテスト
        $response->assertStatus(403);
    }

    /** @test */
    public function ゲストが登録した書籍を認証なしで削除できる()
    {
        $book = Book::factory()->create(['user_id' => self::GUEST_USER_ID]);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
    }

    /** @test */
    public function 他の通常ユーザーが登録した書籍の削除は拒否される()
    {
        $otherUser = User::factory()->create();
        $book = Book::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->deleteJson("/api/v1/books/{$book->id}");

        // ステータス403が返ることをテスト、データが消えていないことをテスト
        $response->assertStatus(403);
        $this->assertDatabaseHas('books', ['id' => $book->id]);

    }


}
