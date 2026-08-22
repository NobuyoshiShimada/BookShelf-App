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

    /**
     * 認証なしで書籍一覧を取得
     *
     * 認証（トークン）を持たない未ログインの一般ユーザーであっても、
     * 全登録ユーザーの書籍一覧をページネーション形式で正常に取得（200）できるかを検証。
     */
    public function test_認証無しで書籍一覧を取得(): void
    {
        Book::factory()->count(2)->create([
            'user_id' => $this->ownerUser->id,
        ]);

        Book::factory()->create([
            'user_id' => $this->otherUser->id,
        ]);

        $response = $this->getJson('/api/v1/');

        $response->assertStatus(200)->assertJsonCount(3, 'data');
    }

    /**
     * 認証なしでの新規書籍登録
     *
     * 認証トークンを付与せずに書籍登録APIにリクエストを送信した場合、
     * サーバー側で安全に登録を拒否し、401 Unauthenticated を返却するかを検証。
     */
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

        $response = $this->postJson('/api/v1/', $bookData, ['Accept' => 'application/json']);
        $response->assertStatus(401);
    }

    /**
     * sanctum認証済みでの新規書籍登録
     *
     * 有効なSanctum認証を通したログインユーザーであれば、書籍情報を新規登録（201）でき、
     * データベース側にも認証された本人の `user_id` で正しくレコードが保存されるかを検証。
     */
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

        $response = $this->postJson('/api/v1/', $bookData);

        $response->assertStatus(201);

        // データベースにuser_idに認証された本人のいっで保存されている
        $this->assertDatabaseHas('books', [
            'title' => 'テスト駆動',
            'user_id' => $this->ownerUser->id,
        ]);
    }

    /**
     * 認証なしで書籍の詳細を取得
     *
     * ログインの有無に関わらず、指定された書籍の個別IDに対応する詳細情報を
     * パブリックに正常取得（200）し、正しいレスポンス構造が返されるかを検証。
     */
    public function test_認証なしで書籍の詳細を取得できる(): void
    {
        $book = Book::factory()->create(['user_id' => $this->ownerUser->id]);

        $response = $this->getJson("/api/v1/books/{$book->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $book->id);
    }

    /**
     * 書籍登録者本人による情報更新
     *
     * 対象の書籍データを過去に登録した「所有者本人」としてSanctum認証を通している場合、
     * 書籍情報を安全にPUT更新（200）し、データベースの値が書き換わるかを検証。
     */
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

    /**
     * 他ユーザーが登録した書籍の更新制限
     *
     * 認証済みユーザーであっても、所有権のない「他人が登録した書籍」を更新しようとした場合、
     * 認可ポリシー（Policy）により処理が403 Forbiddenでブロックされ、拒否メッセージが返るかを検証。
     */
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

    /**
     * 書籍登録者本人による削除処理
     *
     * 対象の書籍データを登録した「所有者本人」がSanctum認証を介してDELETEリクエストを送信した場合、
     * 正常に処理を通過（200）し、データベースからレコードが物理消去されるかを検証。
     */
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

    /**
     * 他ユーザーが登録した書籍の削除制限
     *
     * 所有権のない「他人が登録した書籍」を勝手に削除しようとした場合、
     * 認可ポリシー（Policy）が作動して403 Forbiddenを返し、DB内のデータが安全に保護されるかを検証。
     */
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

    /**
     * 不正なデータによるバリデーションエラー
     *
     * 必須項目であるタイトルを空、または出版日を不正な日付形式にして送信した際、
     * コントローラーに到達する手前で422 Unprocessable Entityを返し、該当カラムのエラーが返却されるかを検証。
     */
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

        $response = $this->postJson('/api/v1/', $invalidData);

        // 💡 期待値: 422 が返り、エラーメッセージが含まれていること
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'published_date']);
    }
}
