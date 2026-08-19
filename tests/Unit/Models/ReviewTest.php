<?php

namespace Tests\Unit\Models;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    /**
     *
     */
    // ユーザーが複数の書籍を登録できるかテスト（1対多）
    public function test_review_belongs_to_user(): void
    {
        // テスト用レビューを1件作成（Factoryにより裏でUser、Bookも自動生成されて紐付く）
        $review = Review::factory()->create();

        // $review->userがUserクラスのインスタンスであるかテスト
        $this->assertInstanceOf(User::class, $review->user);
    }

    // ReviewモデルがBookモデルに正しく属しているかテスト（1対多）
    public function test_review_belongs_to_book(): void
    {
        // テスト用レビューを1件作成
        $review = Review::factory()->create();

        // $review->bookがBookクラスのインステンスであるかテスト
        $this->assertInstanceOf(Book::class, $review->book);
    }

    // Reviewモデルに複数のユーザーが「いいね」できるかテスト（多対多）
    public function test_review_belongs_to_many_liked_by_users(): void
    {
        // テスト用レビューを1件作成
        $review = Review::factory()->create();
        // テスト用ユーザーを2人作成
        $users = User::factory()->count(2)->create();

        // 中間テーブル「review_likes」にユーザーを紐付け
        $review->likedByUsers()->attach($users->pluck('id'));

        // このレビューに「いいね」したユーザーが2人正しく取得できるかテスト
        $this->assertInstanceOf(Collection::class, $review->likedByUsers);
        $this->assertCount(2, $review->likedByUsers);
        $this->assertInstanceOf(User::class, $review->likedByUsers->first());
    }

    // isLikedByメソッドが正しく true/false を判定できるかテスト
    public function test_review_if_is_liked_by_user(): void
    {
        $review = Review::factory()->create();
        $userWhoLiked = User::factory()->create();
        $userWhoDidNotLike = User::factory()->create();

        // 片方のユーザーにだけ「いいね」を中間テーブルに登録
        $review->likedByUsers()->attach($userWhoLiked->id);

        // いいねしたユーザーを渡すとtrue、していないユーザーはfalse、未ログイン（null）はfalseになるテスト
        $this->assertTrue($review->isLikedBy($userWhoLiked));
        $this->assertFalse($review->isLikedBy($userWhoDidNotLike));
        $this->assertFalse($review->isLikedBy(null));
    }

    /**
     * toggleLikeReview メソッドの状態反転テスト
     *
     * 「未登録なら新規登録（いいねON）」「登録済みなら即時解除（いいねOFF）」を正しくスイッチできるか検証。
     */
    public function test_ユーザーモデルのtoggleLikeReviewメソッドによっていいね状態が正しくトグル反転する(): void
    {
        $user = User::factory()->create();
        $review = Review::factory()->create();

        // 1回目：まだいいねしていない状態からトグルを実行 ➔ 「いいね登録（ON）」
        $user->toggleLikeReview($review->id);

        // 中間テーブルにレコードが挿入され、判定ヘルパーが true を返すことを検証
        $this->assertDatabaseHas('review_likes', [
            'user_id'   => $user->id,
            'review_id' => $review->id,
        ]);
        $this->assertTrue($review->fresh()->isLikedBy($user));

        // 2回目：すでにいいねしている状態から再度トグルを実行 ➔ 「いいね解除（OFF）」
        $user->toggleLikeReview($review->id);

        // 中間テーブルのレコードが安全に消去され、判定ヘルパーが false を返すことを検証
        $this->assertDatabaseMissing('review_likes', [
            'user_id'   => $user->id,
            'review_id' => $review->id,
        ]);
        $this->assertFalse($review->fresh()->isLikedBy($user));
    }
}
