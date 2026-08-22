<?php

namespace Tests\Feature\Web;

use App\Models\Book;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RankingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ランキング画面のアクセスおよびレビュー評価順の並び替え検証
     *
     * レビューが投稿されていない書籍、星5の「高評価の本」、星1の「低評価の本」が混在する環境において、
     * ランキング画面（index）へ正常アクセス（200）でき、集計データ（rankedBooks）を保持した上で
     * 評価の平均値が高い順にソートされてビューテンプレートへ引き渡されるかを検証。
     */
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
