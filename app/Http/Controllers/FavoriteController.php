<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    /**
     * ログイン中の認証ユーザーがお気に入り登録した書籍一覧画面を表示
     *
     * @return View お気に入り書籍一覧画面のビュー
     */
    public function index(): View
    {
        /** @var User */
        $user = Auth::user();

        $books = $user->favoriteBooks()
            ->with(['genres'])
            ->latest('favorites.created_at')
            ->paginate(10);

        return view('favorites.index', compact('books'));
    }

    /**
     * 指定された書籍のお気に入り状態をトグル（登録・解除を反転）
     *
     * @param  Book  $book  トグル対象の書籍モデルインスタンス
     * @return RedirectResponse 直前の画面へのリダイレクトレスポンス
     */
    public function toggle(Book $book): RedirectResponse
    {
        /** @var User */
        $user = Auth::user();

        $status = $user->toggleFavoriteBook($book->id);

        $message = $status === 'detached'
        ? 'お気に入りを解除しました。'
        : 'お気に入りを追加しました。';

        return back()->with('success', $message);
    }
}
