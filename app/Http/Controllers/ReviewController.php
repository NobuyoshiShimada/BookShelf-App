<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReviewRequest;
use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class ReviewController extends Controller
{
    use AuthorizesRequests;

    /**
     * 対象書籍に対する新規レビューのデータベース登録処理
     *
     * @param  ReviewRequest  $request  入力バリデーション済みのリクエスト
     * @param  Book  $book  レビュー対象の書籍モデルインスタンス
     * @return RedirectResponse 書籍詳細画面へのリダイレクトレスポンス
     */
    public function store(ReviewRequest $request, Book $book): RedirectResponse
    {
        $userId = Auth::id();
        $validated = $request->validated();

        $alreadyReviewed = $book->reviews()->where("user_id", $userId)->exists();

        if ($alreadyReviewed) {
            return redirect()->route("books.show", $book)
            ->with("error", "この書籍にはすでにレビューが投稿済みです。1冊につき1件まで投稿できます。");
        }
        $book->reviews()->create([
            'user_id' => $userId,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        return redirect()->route('books.show', $book)
            ->with('success', 'レビューを投稿しました。');
    }

    /**
     * 投稿したレビューの編集画面の表示
     *
     * @param  Review  $review  ルートモデルバインディングされたレビューモデル
     * @return View レビュー編集画面のビュー
     */
    public function edit(Review $review): View
    {
        $this->authorize('update', $review);

        return view('reviews.edit', compact('review'));
    }

    /**
     * 既存レビュー情報の更新処理
     *
     * @param  ReviewRequest  $request  入力バリデーション済みのリクエスト
     * @param  Review  $review  ルートモデルバインディングされたレビューモデル
     * @return RedirectResponse 書籍詳細画面へのリダイレクトレスポンス
     */
    public function update(ReviewRequest $request, Review $review): RedirectResponse
    {
        $this->authorize('update', $review);

        $validated = $request->validated();

        $review->update([
            'rating' => $validated['rating'],
            'comment' => $validated['comment'],
        ]);

        return redirect()->route('books.show', $review->book)
            ->with('success', 'レビューを更新しました。');
    }

    /**
     * 投稿済みレビューデータの削除処理
     *
     * @param \App\Models\Review $review ルートモデルバインディングされたレビューモデル
     * @return \Illuminate\Http\RedirectResponse 書籍詳細画面へのリダイレクトレスポンス
     * @throws \Illuminate\Auth\Access\AuthorizationException 投稿者本人ではないユーザーが削除を試みた場合
     */
    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $review->loadMissing('book');
        $book = $review->book;

        $review->delete();

        return redirect()->route('books.show', $book)
            ->with('success', 'レビューを削除しました。');
    }

    /**
     * 特定のレビューに対する「いいね！」状態をトグル（登録・解除を反転）処理
     *
     * @param string $id いいね対象のレビュー主キーID
     * @return \Illuminate\Http\RedirectResponse 直前の画面へのリダイレクトレスポンス
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException 対象のレビューが存在しない場合
     */
    public function toggle(string $id): RedirectResponse
    {
        $review = Review::findOrFail($id);

        /** @var User $user */
        $user = Auth::user();

        $message = DB::transaction(function () use ($user, $review) {
            $user->toggleLikeReview($review->id);
        });
        $user->toggleLikeReview($review->id);

        $message = $review->fresh()->isLikedBy($user)
            ? "レビューにいいね！を追加しました。"
            : "レビューのいいね！を解除しました。";

        return back()->with("success", $message);
    }
}
