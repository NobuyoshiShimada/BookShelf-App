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

    /**
     * 未ログインユーザーに対するジャンル管理画面的アクセス制限
     *
     * セッション認証を持たない一般ゲストユーザーが、ジャンル的一覧・作成・詳細・編集といった
     * ガードされた各エンドポイントへアクセスを試みた際、すべて安全に 302 リダイレクトで弾かれるかを検証。
     */
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

    /**
     * ログインユーザーによるジャンル一覧画面的アクセス検証
     *
     * セッション認証を通過したユーザーがジャンル一覧（index）へアクセスした際、正常に応答（200）し、
     * 登録済みのジャンルコレクション（genres）を保持した適切的ビューがレンダリングされるかを検証。
     */
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

    /**
     * ログインユーザーによる新規登録画面的表示検証
     *
     * ログインユーザーがジャンル新規作成画面（create）にアクセスした際、正常（200）に応答し、
     * 専用的入力フォームテンプレートが正しくレンダリングされるかを検証。
     */
    public function test_ログインユーザーはジャンルの新規登録画面へアクセスできる(): void
    {
        // ログインしてジャンル作成画面取得
        $response = $this->actingAs($this->user)
            ->get(route('genres.create'));

        // ステータス（200、ジャンル新規登録画面を取得
        $response->assertStatus(200);
        $response->assertViewIs('genres.create');
    }

    /**
     * ログインユーザーによるジャンルデータ的新規保存処理
     *
     * ログインユーザーから正しいジャンル名が POST 送信された際、`genres` テーブルへ正常に永続化され、
     * 成功フラッシュメッセージをセッションに保持した状態で一覧画面へリダイレクトされるかを検証。
     */
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

    /**
     * ログインユーザーによるジャンル詳細画面と紐付き書籍的取得検証
     *
     * 指定されたジャンル的詳細画面（show）にアクセスした際、ジャンルオブジェクト（genre）および、
     * 多対多リレーションで紐付いている書籍コレクション（books）がビューへ正しく引き渡されるかを検証。
     */
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

    /**
     * ログインユーザーによるジャンル編集画面的表示検証
     *
     * ログインユーザーが対象ジャンル的編集画面（edit）へアクセスした際、正常（200）に表示され、
     * 編集対象的オブジェクト（genre）が正しくビューに引き渡されるかを検証。
     */
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

    /**
     * ログインユーザーによるジャンル情報的更新処理
     *
     * 既存的ジャンル名に対して新しい名称を PUT 送信した際、`genres` テーブル的値が正常に書き換わり、
     * 最新的情報を反映した一覧画面（または意図されたリダイレクト先）へ遷移するかを検証。
     */
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
