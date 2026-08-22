<?php

namespace Tests\Feature\Web;

use App\Models\Book;
use App\Models\Genre;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class AdvancedBookSearchSortTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Book $book1;

    private Book $book2;

    protected function setUp(): void
    {
        parent::setUp();

        // テストユーザー作成
        $this->user = User::factory()->create([
            'email' => 'yamada@example.com',
        ]);
        // ジャンルの作成
        $genre = Genre::factory()->create(['name' => 'プログラミング']);

        // テスト用書籍の作成（検索、ソート、ISBNテスト用）
        $this->book1 = Book::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Laravel実践',
            'author' => 'テスト太郎',
            'isbn' => '1234567890123',
            'created_at' => Carbon::now()->subDays(2),
        ]);
        $this->book1->genres()->attach($genre->id);

        $this->book2 = Book::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'PHP問題集',
            'author' => 'サンプル次郎',
            'isbn' => '0987654321098',
            'created_at' => Carbon::now(),
        ]);
        $this->book2->genres()->attach($genre->id);
    }

    /**
     * キーワード検索およびジャンルフィルタリング
     *
     * タイトル、著者名による部分一致検索、およびジャンルIDによる絞り込みが
     * 正しくクエリに反映され、該当する書籍のみが画面に描画されるかを検証。
     */
    public function test_キーワード検索、フィルタ(): void
    {
        // キーワード検索（タイトル）
        $response = $this->actingAs($this->user)->get(route('books.index', ['keyword' => 'Laravel']));
        $response->assertStatus(200)->assertSee('Laravel実践')->assertDontSee('PHP問題集');
        // キーワード検索（著書名）
        $response = $this->actingAs($this->user)->get(route('books.index', ['keyword' => 'テスト']));
        $response->assertStatus(200)->assertSee('Laravel実践')->assertDontSee('PHP問題集');
        // ジャンル検索
        $genre = Genre::where('name', 'プログラミング')->first();
        $response = $this->actingAs($this->user)->get(route('books.index', ['genre' => $genre->id]));
        $response->assertStatus(200)->assertSee('Laravel実践');
    }

    /**
     * 一覧画面の複数ソート機能
     *
     * クエリパラメータ（sort）の指定に基づき、「最新順」「古い順」「レビュー評価順」
     * 「タイトル五十音順」でレコードが期待通りの並び順（SeeInOrder）で取得できるかを検証。
     */
    public function test_ソート機能(): void
    {
        Review::factory()->create([
            'user_id' => $this->user->id,
            'book_id' => $this->book1->id,
            'rating' => 5,
        ]);
        Review::factory()->create([
            'user_id' => $this->user->id,
            'book_id' => $this->book2->id,
            'rating' => 3,
        ]);
        // 最新順でのソート
        // 期待される順序: book2 (今日) ➔ book1 (2日前)
        $response = $this->actingAs($this->user)->get(route('books.index', ['sort' => 'latest']));
        $response->assertStatus(200);
        $response->assertSeeInOrder(['PHP問題集', 'Laravel実践']);

        // 古い順でのソート
        // 期待される順序: book1 (2日前) ➔ book2 (今日)
        $response = $this->actingAs($this->user)->get(route('books.index', ['sort' => 'oldest']));
        $response->assertStatus(200);
        $response->assertSeeInOrder(['Laravel実践', 'PHP問題集']);

        // 評価順のソート
        // 期待される順序: book1 (星5) ➔ book2 (星3)
        $response = $this->actingAs($this->user)->get(route('books.index', ['sort' => 'rating']));
        $response->assertStatus(200);
        $response->assertSeeInOrder(['Laravel実践', 'PHP問題集']);

        // タイトル順でのソート
        // 期待される順序: L (Laravel実践開発) ➔ P (PHPオブジェクト指向) ※五十音・アルファベット順
        $response = $this->actingAs($this->user)->get(route('books.index', ['sort' => 'title']));
        $response->assertStatus(200);
        $response->assertSeeInOrder(['Laravel実践', 'PHP問題集']);
    }

    /**
     * 検索条件を維持した状態でのページネーション遷移
     *
     * 複数件の書籍が存在する環境で、検索キーワード（keyword）を付与したまま
     * 2ページ目（page=2）へ遷移した際にも条件が消失せず引き継がれるかを検証。
     */
    public function test_検索時条件を維持したままページ遷移(): void
    {
        Book::factory()->count(10)->sequence(fn ($sequence) => [
            'user_id' => $this->user->id,
            'title' => 'Laravel応用ガイド',
            'author' => 'テスト太郎',
            'isbn' => '2345'.str_pad($sequence->index, 9, '0', STR_PAD_LEFT),
        ])
            ->create();

        $response = $this->actingAs($this->user)->get(route('books.index', [
            'keyword' => 'Laravel',
            'per_page' => 10,
        ]));

        $response->assertStatus(200);

        $response->assertSee('keyword=Laravel')->assertSee('page=2');

        $responseNextPage = $this->actingAs($this->user)->get(route('books.index', [
            'keyword' => 'Laravel',
            'per_page' => 10,
            'page' => 2,
        ]));

        $responseNextPage->assertStatus(200)->assertSee('Laravel');
    }

    /**
     * 外部API連携（Google Books API等）のモック通信検証
     *
     * `Http::fake` を用いて外部ネットワーク通信を遮断・疑似応答化し、
     * 取得した生データをアプリケーション仕様のJSON構造へ変換して取得できるかを検証。
     */
    public function test_外部api連携のモック化(): void
    {
        Http::fake([
            '*' => Http::response([
                'items' => [
                    [
                        'volumeInfo' => [
                            'title' => 'モックされた外部書籍タイトル',
                            'authors' => ['モック著者'],
                            'industryIdentifiers' => [
                                ['type' => 'ISBN_13', 'identifier' => '3456789012345'],
                            ],
                            'description' => 'これはHttp::fakeによって作成されたテストデータです',
                            'imageLinks' => ['thumbnail' => 'https://example.com'],
                        ],
                    ],
                ],
            ], 200),
        ]);

        $response = $this->actingAs($this->user)->get(route('books.search-isbn', [
            'isbn' => '3456789012345',
        ]));

        $response->assertStatus(200)
            ->assertJsonPath('title', 'モックされた外部書籍タイトル')
            ->assertJsonPath('author', 'モック著者');
    }

    /**
     * 外部API側で書籍がヒットしなかった（該当なし）場合の処理
     *
     * 外部APIから検索結果が空（itemsが空配列）で返却された際、
     * システム内部で適切に検知し、ユーザーへ404エラーおよび日本語の補足文を返せるかを検証。
     */
    public function test_外部apiが「該当無し」を返した時の404(): void
    {
        Http::fake([
            '*' => Http::response(['items' => []], 200),
        ]);

        $response = $this->actingAs($this->user)->get(route('books.search-isbn', [
            'isbn' => '9999999999999',
        ]));

        $response->assertStatus(404)
            ->assertJsonPath('error', '該当する書籍情報が見つかりませんでした。');
    }

    /**
     * 外部APIのサーバーダウン・通信障害時のフェイルセーフ
     *
     * 外部API側がステータス500等のエラーを返却、あるいは接続不能になった場合、
     * アプリケーションがクラッシュせず、安全な制御（404等への丸め処理）が作動するかを検証。
     */
    public function test_外部apiが「通信障害、サーバーダウン」を起こした時の500(): void
    {
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $response = $this->actingAs($this->user)->get(route('books.search-isbn', [
            'isbn' => '3456789012345',
        ]));

        $response->assertStatus(404);
    }
}
