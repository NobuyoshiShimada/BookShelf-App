<?php

namespace Tests\Feature\Web;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LikeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Book $book;

    // 認証用ユーザー1人とテスト用の1冊書籍作成
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->book = Book::factory()->create();
    }

    /**
     * 特定のレビューに対する「いいね！」状態の反転（登録・解除トグル）処理の検証
     *
     * ログインユーザーが対象の書籍レビューに対して1回目のリクエスト（いいね！）を送信した際に
     * 中間テーブル（review_likes）にレコードが正常保存（永続化）され、全く同じリクエストを
     * 2回目に連続で送信した際、レコードが物理削除（Missing）されるトグル制御のライフサイクルを検証。
     */
    public function test_ログインユーザーはレビューのいいねの登録・解除ができる(): void
    {
        // テスト用のレビュー作成
        $review = Review::factory()->create([
            'book_id' => $this->book->id,
            'user_id' => User::factory()->create()->id,
        ]);

        // ログインして、いいねの追加（1回目の押下）
        $response = $this->actingAs($this->user)
            ->from(route('books.show', $this->book))
            ->post(route('reviews.like', ['book' => $review->id]));

        // データベースに登録されたか確認のテスト
        $this->assertDatabaseHas('review_likes', [
            'user_id' => $this->user->id,
            'review_id' => $review->id,
        ]);
        // リダイレクト先のテスト
        $response->assertRedirect(route('books.show', $this->book));

        // いいねの解除（2回目の押下）
        $response = $this->actingAs($this->user)
            ->from(route('books.show', $this->book))
            ->post(route('reviews.like', ['book' => $review->id]));

        // データベースから削除されたか確認のテスト
        $this->assertDatabaseMissing('review_likes', [
            'user_id' => $this->user->id,
            'review_id' => $review->id,
        ]);

        // リダイレクト先のテスト
        $response->assertRedirect(route('books.show', $this->book));
    }
}
