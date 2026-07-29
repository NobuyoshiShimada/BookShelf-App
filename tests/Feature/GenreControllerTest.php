<?php

namespace Tests\Feature;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenreControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    // 認証用ユーザーを作成
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // 未ログインユーザーのアクセス制限テスト
    public function test_guest_access(): void
    {
        // テスト用にジャンルを1件作成
        $genre = Genre::factory()->create();

        $this->assertGuest();

        $this->get(route('genres.index'))->assertStatus(302);
        $this->get(route('genres.create'))->assertStatus(302);
        $this->get(route('genres.show', $genre))->assertStatus(302);
        $this->get(route('genres.edit', $genre))->assertStatus(302);
    }

    // Indexのテスト
    public function test_index(): void
    {
        // テスト用にジャンルを2件作成
        Genre::factory()->create(['name' => 'B Genre']);
        Genre::factory()->create(['name' => 'A Genre']);

        // ログインしてジャンル一覧情報を取得
        $response = $this->actingAs($this->user)
            ->get(route('genres.index'));

        // ステータス、画面、情報取得をテスト
        $response->assertStatus(200);
        $response->assertViewIs('genres.index');
        $response->assertViewHas('genres');
    }

    // Createのテスト
    public function test_create(): void
    {
        // ログインしてジャンル作成画面取得
        $response = $this->actingAs($this->user)
            ->get(route('genres.create'));

        // ステータス、画面を取得
        $response->assertStatus(200);
        $response->assertViewIs('genres.create');
    }

    // Storeのテスト
    public function test_store(): void
    {
        // テスト用のジャンルを1件作成
        $genreData = ['name' => 'sample'];

        // ログインして新規登録処理
        $response = $this->actingAs($this->user)
            ->post(route('genres.store'), $genreData);

        $this->assertDatabaseHas('genres', ['name' => 'sample']);
        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success', 'ジャンル「'.$genreData['name'].'」を新しく登録しました。');
    }

    // Showのテスト
    public function test_show(): void
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

        // ステータス、画面、情報取得のテスト
        $response->assertStatus(200);
        $response->assertViewIs('genres.show');
        $response->assertViewHas('genre', $genre);
        $response->assertViewHas('books');
    }

    // Editのテスト
    public function test_edit(): void
    {
        // テスト用にジャンルを1件作成
        $genre = Genre::factory()->create();

        // ログインして編集画面情報取得
        $response = $this->actingAs($this->user)
            ->get(route('genres.edit', $genre));

        // ステータス、画面、情報取得のテスト
        $response->assertStatus(200);
        $response->assertViewIs('genres.edit');
        $response->assertViewHas('genre', $genre);
    }

    // Updateのテスト
    public function test_update(): void
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

        // 更新後のリダイレクト先、更新成功時のメッセージのテスト
        $genre->refresh();
        $response->assertRedirect(route('genres.index', $genre));
        $response->assertSessionHas('success', 'ジャンル「'.$genre->name.'」の情報を更新しました。');
    }

    // Destroyのテスト
    public function test_destroy(): void
    {
        $genre = Genre::factory()->create();
        $book = Book::factory()->create();
        $genre->books()->attach($book->id);

        $response = $this->actingAs($this->user)
            ->delete(route('genres.destroy', $genre));

        $this->assertDatabaseMissing('genres', ['id' => $genre->id]);
        $this->assertDatabaseMissing('book_genre', ['genre_id' => $genre->id]);

        $response->assertRedirect(route('genres.index'));
        $response->assertSessionHas('success', 'ジャンル「'.$genre->name.'」を削除しました。');
    }
}
