<?php

namespace Tests\Unit\Services;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\Genre;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use App\Services\ReadingReportsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 🧪 ReadingReportsService ユニット（単体）テスト
 */
class ReadingReportsServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private ReadingReportsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->service = new ReadingReportsService();
    }

    /**
     * 🧪 正常系: 基本統計（summary）と星数分布（rating_distribution）の集計検証
     *
     * 💡 修正仕様:
     * rating_distribution が Collection オブジェクトであるため、
     * 連想配列としてのアクセスではなく「->get(キー)」メソッドを用いて厳格にアサートします。
     */
    public function test_generateUserStats_基本統計と評価星数の分布を正確に算出する(): void
    {
        // 💡 独立した5冊の書籍データを明示的に用意
        $book1 = Book::factory()->create();
        $book2 = Book::factory()->create();
        $book3 = Book::factory()->create();
        $book4 = Book::factory()->create();
        $book5 = Book::factory()->create();

        // ① レビューの作成（平均: 4.0）
        Review::factory()->create([
            'user_id' => $this->user->id,
            'book_id' => $book1->id,
            'rating'  => 5
        ]);
        Review::factory()->create([
            'user_id' => $this->user->id,
            'book_id' => $book2->id,
            'rating'  => 3
        ]);

        // ② 読了済みの計画を2件作成
        ReadingPlan::factory()->create([
            'user_id' => $this->user->id,
            'book_id' => $book3->id,
            'status'  => ReadingPlanStatus::Completed->value
        ]);
        ReadingPlan::factory()->create([
            'user_id' => $this->user->id,
            'book_id' => $book4->id,
            'status'  => ReadingPlanStatus::Completed->value
        ]);

        // 統計集計の実行
        $stats = $this->service->generateUserStats($this->user);

        // 1. 基本統計サマリーの検証
        $this->assertEquals(2, $stats['summary']['total_reviews']);
        $this->assertEquals(2, $stats['summary']['books_read']);
        $this->assertEquals('4.0', $stats['summary']['average_rating']);

        // 2. 評価分布（Collection）の検証
        // 💡 修正: ->get() メソッドを使用して、キー 0〜4 の件数を正しく抽出
        $distribution = $stats['rating_distribution'];
        $this->assertEquals(0, $distribution->get(0)); // キー 0 ➔ ★1は0件
        $this->assertEquals(1, $distribution->get(2)); // キー 2 ➔ ★3は1件
        $this->assertEquals(1, $distribution->get(4)); // キー 4 ➔ ★5は1件
    }

    /**
     * 🧪 正常系: 高評価書籍TOP5（top_rated_books）の並び順と閾値制限の検証
     */
    public function test_generateUserStats_評価4以上の書籍のみを最高評価順に最大5件抽出する(): void
    {
        $bookHigh = Book::factory()->create(['title' => '最高本']);
        $bookMid  = Book::factory()->create(['title' => '普通本']);
        $bookLow  = Book::factory()->create(['title' => '除外本']);

        Review::factory()->create(['user_id' => $this->user->id, 'book_id' => $bookHigh->id, 'rating' => 5]);
        Review::factory()->create(['user_id' => $this->user->id, 'book_id' => $bookMid->id, 'rating' => 4]);
        Review::factory()->create(['user_id' => $this->user->id, 'book_id' => $bookLow->id, 'rating' => 3]);

        $stats = $this->service->generateUserStats($this->user);
        $topBooks = $stats['top_rated_books'];

        $this->assertCount(2, $topBooks);
        $this->assertEquals('最高本', $topBooks[0]['title']);
        $this->assertEquals(5, $topBooks[0]['rating']);
        $this->assertEquals('普通本', $topBooks[1]['title']);
        $this->assertEquals(4, $topBooks[1]['rating']);
    }

    /**
     * 🧪 正常系: ジャンル別評価傾向ランキング（genre_ratings）の算出検証
     */
    public function test_generateUserStats_ジャンルごとの平均評価を算出し高評価順にランキングする(): void
    {
        $genreAnime = Genre::factory()->create(['name' => 'アニメ']);
        $genreNovel = Genre::factory()->create(['name' => '小説']);

        $book1 = Book::factory()->create();
        $book1->genres()->attach($genreAnime->id);

        $book2 = Book::factory()->create();
        $book2->genres()->attach($genreNovel->id);

        Review::factory()->create(['user_id' => $this->user->id, 'book_id' => $book1->id, 'rating' => 5]);
        Review::factory()->create(['user_id' => $this->user->id, 'book_id' => $book2->id, 'rating' => 3]);

        $stats = $this->service->generateUserStats($this->user);
        $genreRatings = $stats['genre_ratings'];

        $this->assertCount(2, $genreRatings);
        $this->assertEquals('アニメ', $genreRatings[0]['name']);
        $this->assertEquals('5.0', $genreRatings[0]['average_rating']);
        $this->assertEquals('小説', $genreRatings[1]['name']);
        $this->assertEquals('3.0', $genreRatings[1]['average_rating']);
    }
}
