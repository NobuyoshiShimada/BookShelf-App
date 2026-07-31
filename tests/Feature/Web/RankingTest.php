<?php

namespace Tests\Feature\Web;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    // Rankingのテスト
    public function test_ランキング画面にアクセスして評価が良い順に表示される(): void
    {
        // テスト用のユーザーを1件作成
        $book = Book::factory()->create();

        // テスト用の書籍と、レビューを2件作成
        $highBook = Book::factory()->create(['title' => '高評価の本']);
        Review::factory()->create([
            'book_id' => $highBook->id,
            'rating' => 5,
        ]);
        $lowBook = Book::factory()->create(['title' => '低評価の本']);
        Review::factory()->create([
            'book_id' => $lowBook->id,
            'rating' => 1,
        ]);

        // ランキングの画面を取得
        $response = $this->get(route('ranking.index'));

        // ステータス（200）、ランキング一覧画面、取得情報をテスト
        $response->assertStatus(200);
        $response->assertViewIs('ranking.index');
        $response->assertViewHas('rankedBooks');
    }
}
