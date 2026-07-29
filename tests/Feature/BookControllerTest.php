<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Override;
use Symfony\Component\Routing\Loader\ProtectedPhpFileLoader;
use Tests\TestCase;

class BookControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    /**
     * A basic feature test example.
     */
    #[Override]
    Protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // indexのテスト
    public function test_index(): void
    {
        // テスト用のジャンルを1件作成
        $genre = Genre::factory()->create();
        // テスト用の書籍を1冊作成
        $book = Book::factory()->create();
        // この書籍にジャンルを紐付ける
        $book->genres()->attach($genre->id);

        // 書籍一覧の情報を取得
        $response = $this->get(route('books.index'));

        // ステータス、ルート、取得情報をテスト
        $response->assertStatus(200);
        $response->assertViewIs('books.index');
        $response->assertViewHas('books');
    }

    // createのテスト
    public function test_create(): void
    {
        // テスト用にジャンルを3件を作成
        Genre::factory()->count(3)->create();

        // ログインして書籍新規作成画面の情報を取得
        $response = $this->actingAs($this->user)
        ->get(route('books.create'));

        // ステータス、ルート、取得情報をテスト
        $response->assertStatus(200);
        $response->assertViewIs('books.create');
        $response->assertViewHas('genres');
    }

    // Storeのテスト
    public function test_store(): void
    {
        // テスト用にジャンルを2件作成
        $genres = Genre::factory()->count(2)->create();

        // テスト用の書籍のダミーデータ作成
        $bookDate = [
            'title' => 'サンプル書籍',
            'author' => 'テスト太郎',
            'isbn' => '1234567890123',
            'published_date' => '2000-12-12',
            'description' => 'テストの説明です。',
            'image_url' => 'https://example.com',
            'genres' => $genres->pluck('id')->toArray(),
        ];
        // ログインして新規登録
        $response = $this->actingAs($this->user)
        ->post(route('books.store'), $bookDate);

        // データベース登録確認
        $this->assertDatabaseHas('books', [
            'user_id' => $this->user->id,
            'title' => 'サンプル書籍',
            'author' => 'テスト太郎',
        ]);
        // 中間テーブルの紐付け確認
        $book = Book::where('title', 'サンプル書籍')->first();
        $this->assertCount(2, $book->genres);

        // 登録後のリダイレクト先、登録成功時のメッセージのテスト
        $response->assertRedirect(route('books.index'));
        $response->assertSessionHas('success', '書籍「' . $book->title . '」を新しく登録しました。');
    }

    // Showのテスト
    public function test_show(): void
    {
        // テスト用の書籍を1冊作成
        $book = Book::factory()->create();

        // 書籍詳細を取得
        $response = $this->get(route('books.show', $book));

        // ステータス、ルート、取得情報をテスト
        $response->assertStatus(200);
        $response->assertViewIs('books.show');
        $response->assertViewHas('book');
    }

    // Editのテスト（作成者本人）
    public function test_edit_owner(): void
    {
        // テスト用に書籍を1冊作成
        $book = Book::factory()->create(['user_id' => $this->user->id]);
        // テスト用にジャンルを2件作成
        Genre::factory()->count(2)->create();

        // ログインして編集画面情報を取得
        $response = $this->actingAs($this->user)
        ->get(route('books.edit', $book));

        // ステータス、ルート、取得情報をテスト
        $response->assertStatus(200);
        $response->assertViewIs('books.edit');
        $response->assertViewHas('book');
        $response->assertViewHas('genres');
    }

    // Editのテスト（他人によるアクセス）
    public function test_not_owner(): void
    {
        // テスト用の他人ユーザー1人作成
        $otherUser = User::factory()->create();
        // テスト用に他人が作成者の書籍を1冊作成
        $book = Book::factory()->create(['user_id' => $otherUser->id]);

        // ログインして編集画面情報を取得するがエラーになる
        $response = $this->actingAs($this->user)
        ->get(route('books.edit', $book));

        $response->assertStatus(403);
    }

    // Updateのテスト
    public function test_update(): void
    {
        // テスト用のユーザーを1人作成
        $book = Book::factory()->create(['user_id' => $this->user->id]);
        // テスト用のジャンルを2件作成
        $genres = Genre::factory()->count(2)->create();

        // テスト用の更新情報
        $updatedData = [
            'title' => '更新後のタイトル',
            'author' => '更新後の著者',
            'isbn' => '0987654321123',
            'published_date' => '2010-12-12',
            'description' => '更新後の説明',
            'image_url' => 'https://example.example.com',
            'genres' => $genres->pluck('id')->toArray(),
        ];

        // ログインして更新処理
        $response = $this->actingAs($this->user)
        ->put(route('books.update', $book), $updatedData);

        // データベース登録確認
        $this->assertDatabaseHas('books', [
            'id' => $book->id,
            'title' => '更新後のタイトル',
        ]);

        // 更新後のリダイレクト先、更新成功時のメッセージのテスト
        $book->refresh();
        $response->assertRedirect(route('books.show', $book));
        $response->assertSessionHas('success', '書籍「' . $book->title . '」の情報を更新しました。');
    }

    // Destroyのテスト
    public function test_destroy(): void
    {
        // テスト用のユーザーを1人作成
        $book = Book::factory()->create(['user_id' => $this->user->id]);
        // テスト用のジャンル1件作成
        $genre = Genre::factory()->create();
        // 書籍にジャンルを紐付ける
        $book->genres()->attach($genre->id);

        // ログインして削除する
        $response = $this->actingAs($this->user)
        ->delete(route('books.destroy', $book));

        // データベースから削除されているか確認
        $this->assertDatabaseMissing('books', ['id' => $book->id]);
        // 中間テーブルの紐付けが解除されているか確認
        $this->assertDatabaseMissing('book_genre', ['book_id' => $book->id]);

        // 削除後のリダイレクト先、削除成功時のメッセージのテスト
        $response->assertRedirect(route('books.index'));
        $response->assertSessionHas('success', '書籍「' . $book->title . '」をデータベースから完全に削除しました。');
    }

    // Rankingのテスト
    public function test_Ranking(): void
    {
        // テスト用の書籍を1冊作成
        $book = Book::factory()->create();
        // テスト用のレビューを1件作成
        Review::factory()->create([
            'book_id' => $book->id,
            'rating' => 5,
        ]);

        // ランキングの画面を取得
        $response = $this->get(route('ranking.index'));

        // ステータス、ルート、取得情報をテスト
        $response->assertStatus(200);
        $response->assertViewIs('ranking.index');
        $response->assertViewHas('rankedBooks');
    }
}
