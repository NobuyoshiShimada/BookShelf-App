<?php

namespace Tests\Feature\Web;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    // 認証用ユーザーを作成
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_未ログインユーザーのアクセス制限(): void
    {
        // テスト用にジャンルを1件作成
        $genre = Genre::factory()->create();

        $this->assertGuest();

        // 認証が必要なページはすべてリダイレクト（302）されることをテスト
        $this->get(route('genres.index'))->assertStatus(302);
        $this->get(route('genres.create'))->assertStatus(302);
        $this->get(route('genres.show', $genre))->assertStatus(302);
        $this->get(route('genres.edit', $genre))->assertStatus(302);
    }

    public function test_ログインユーザーはジャンル一覧画面のアクセスができる(): void
    {
        // テスト用にジャンルを2件作成
        Genre::factory()->create(['name' => 'B Genre']);
        Genre::factory()->create(['name' => 'A Genre']);

        // ログインしてジャンル一覧情報を取得
        $response = $this->actingAs($this->user)
            ->get(route('genres.index'));

        // ステータス（200）、ジャンル一覧画面、情報取得をテスト
        $response->assertStatus(200);
        $response->assertViewIs('genres.index');
        $response->assertViewHas('genres');
    }

    public function test_ログインユーザーはジャンルの新規登録画面へアクセスできる(): void
    {
        // ログインしてジャンル作成画面取得
        $response = $this->actingAs($this->user)
            ->get(route('genres.create'));

        // ステータス（200、ジャンル新規登録画面を取得
        $response->assertStatus(200);
        $response->assertViewIs('genres.create');
    }

    public function test_ログインユーザーは新規ジャンルの登録処理ができる(): void
    {
        // テスト用のジャンルを1件作成
        $genreData = ['name' => 'sample'];

        // ログインして新規登録処理
        $response = $this->actingAs($this->user)
            ->post(route('genres.store'), $genreData);

        // データベースに新規登録したジャンルがあるかテスト
        $this->assertDatabaseHas('genres', ['name' => 'sample']);
        // 新規登録後のリダイレクト先（genres.index）、新規登録後のメッセージのテスト
        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success', 'ジャンル「'.$genreData['name'].'」を新しく登録しました。');
    }

    public function test_ログインユーザーはジャンル詳細画面へアクセスができる(): void
    {
        // テスト用のジャンル1件作成
        $genre = Genre::factory()->create();
        // テスト用の書籍1冊作成
        $book = Book::factory()->create();
        // ジャンルを書籍に紐付ける
        $genre->books()->attach($book->id);

        // ログインしてジャンル詳細画面を取得
        $response = $this->actingAs($this->user)
            ->get(route('genres.show', $genre));

        // ステータス（200）、ジャンル詳細画面、情報取得のテスト
        $response->assertStatus(200);
        $response->assertViewIs('genres.show');
        $response->assertViewHas('genre', $genre);
        $response->assertViewHas('books');
    }

    public function test_ログインユーザーはジャンル編集画面のアクセスができる(): void
    {
        // テスト用にジャンルを1件作成
        $genre = Genre::factory()->create();

        // ログインして編集画面情報取得
        $response = $this->actingAs($this->user)
            ->get(route('genres.edit', $genre));

        // ステータス（200）、編集画面、情報取得のテスト
        $response->assertStatus(200);
        $response->assertViewIs('genres.edit');
        $response->assertViewHas('genre', $genre);
    }

    public function test_ログインユーザーはジャンルの更新処理ができる(): void
    {
        // テスト用にジャンルを1件作成
        $genre = Genre::factory()->create([
            'name' => '古い名前',
        ]);
        // テスト用に更新用データを作成
        $updatedData = ['name' => '新しい名前'];

        // ログインして更新処理
        $response = $this->actingAs($this->user)
            ->put(route('genres.update', $genre), $updatedData);

        // データベースに登録されているかテスト
        $this->assertDatabaseHas('genres', [
            'id' => $genre->id,
            'name' => '新しい名前',
        ]);

        // 更新後のリダイレクト先（genres.index）、更新成功時のメッセージのテスト
        $genre->refresh();
        $response->assertRedirect(route('genres.index', $genre));
        $response->assertSessionHas('success', 'ジャンル「'.$genre->name.'」の情報を更新しました。');
    }

    public function test_ログインユーザーはジャンルの削除ができる(): void
    {
        // テスト用にジャンルを1件作成
        $genre = Genre::factory()->create();
        // テスト用に書籍を1件作成
        $book = Book::factory()->create();
        // ジャンルを書籍に紐付ける
        $genre->books()->attach($book->id);

        // ログインしてジャンルを削除する
        $response = $this->actingAs($this->user)
            ->delete(route('genres.destroy', $genre));

        // データベースに書籍に紐付けたジャンルが削除されたかテスト
        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
        $this->assertDatabaseMissing('book_genre', ['genre_id' => $genre->id]);

        // 削除後のリダイレクト先（genres.index）、削除成功時のメッセージのテスト
        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success', 'ジャンル「'.$genre->name.'」を削除しました。');
    }
}
