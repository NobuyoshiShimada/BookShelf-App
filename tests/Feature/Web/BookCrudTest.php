<?php

namespace Tests\Feature\Web;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    // 認証用ユーザー
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_未ログインユーザーのアクセス制限(): void
    {
        // テスト用の書籍を1件作成
        $book = Book::factory()->create();

        // 認証が必要なページはすべてリダイレクト（302）されることをテスト
        $this->get(route('books.create'))->assertStatus(302);
        $this->post(route('books.store'))->assertStatus(302);
        $this->get(route('books.edit', $book))->assertStatus(302);
        $this->put(route('books.update', $book))->assertStatus(302);
        $this->delete(route('books.destroy', $book))->assertStatus(302);
    }

    public function test_書籍一覧の画面にアクセスできる(): void
    {
        // テスト用のジャンルを1件作成
        $genre = Genre::factory()->create();
        // テスト用の書籍を1件作成
        $book = Book::factory()->create();
        // この書籍にジャンルを紐付ける
        $book->genres()->attach($genre->id);

        // 書籍一覧の情報を取得
        $response = $this->get(route('books.index'));

        // ステータス、画面、取得情報をテスト
        $response->assertStatus(200);
        $response->assertViewIs('books.index');
        $response->assertViewHas('books');
    }

    public function test_ログインユーザーは新規書籍登録画面にアクセスできる(): void
    {
        // テスト用にジャンルを3件を作成
        Genre::factory()->count(3)->create();

        // ログインして書籍新規作成画面の情報を取得
        $response = $this->actingAs($this->user)
            ->get(route('books.create'));

        // ステータス(200)、新規書籍登録画面へのアクセス、取得情報をテスト
        $response->assertStatus(200);
        $response->assertViewIs('books.create');
        $response->assertViewHas('genres');
    }

    public function test_ログインユーザーは新規書籍登録処理ができる(): void
    {
        // テスト用にジャンルを2件作成
        $genres = Genre::factory()->count(2)->create();

        // テスト用の書籍の詳細情報1件作成
        $bookDate = [
            'title' => 'サンプル書籍',
            'author' => 'テスト太郎',
            'isbn' => '1234567890123',
            'published_date' => '2000-12-12',
            'description' => 'テストの説明です。',
            'image_url' => 'https://example.com',
            'genres' => $genres->pluck('id')->toArray(),
        ];
        // ログインして新規登録処理
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

        // 登録後のリダイレクト先（books.index）、登録成功時のメッセージのテスト
        $response->assertRedirect(route('books.index'));
        $response->assertSessionHas('success', '書籍「'.$book->title.'」を新しく登録しました。');
    }

    public function test_未ログインユーザーは書籍詳細にアクセスできる(): void
    {
        // テスト用の書籍を1件作成
        $book = Book::factory()->create();

        // 書籍詳細を取得
        $response = $this->get(route('books.show', $book));

        // ステータス（200）、書籍詳細画面へアクセス、取得情報をテスト
        $response->assertStatus(200);
        $response->assertViewIs('books.show');
        $response->assertViewHas('book');
    }

    public function test_ログインユーザー本人が登録した書籍の編集画面はアクセスできる(): void
    {
        // テスト用に書籍を1件作成
        $book = Book::factory()->create(['user_id' => $this->user->id]);
        // テスト用にジャンルを2件作成
        Genre::factory()->count(2)->create();

        // ログインして編集画面情報を取得
        $response = $this->actingAs($this->user)
            ->get(route('books.edit', $book));

        // ステータス（200）、書籍編集画面へのアクセス、取得情報をテスト
        $response->assertStatus(200);
        $response->assertViewIs('books.edit');
        $response->assertViewHas('book');
        $response->assertViewHas('genres');
    }

    public function test_ログインユーザーは他人が登録した書籍の編集画面はアクセスできない(): void
    {
        // テスト用の他人ユーザー1件作成
        $otherUser = User::factory()->create();
        // テスト用に他人が作成者の書籍を1件作成
        $book = Book::factory()->create(['user_id' => $otherUser->id]);

        // ログインして編集画面情報を取得するがエラーになる
        $response = $this->actingAs($this->user)
            ->get(route('books.edit', $book));

        // ステータス（403）
        $response->assertStatus(403);
    }

    public function test_ログインユーザー本人が登録した書籍は更新処理ができる(): void
    {
        // テスト用のユーザーを1件作成
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
        $response->assertSessionHas('success', '書籍「'.$book->title.'」の情報を更新しました。');
    }

    public function test_ログインユーザー本人が登録した書籍は削除ができる(): void
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
        $response->assertSessionHas('success', '書籍「'.$book->title.'」をデータベースから完全に削除しました。');
    }
}
