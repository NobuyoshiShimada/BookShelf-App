<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;


class FavoriteController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
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

        $message = DB::transaction(function () use ($user, $book) {
            $status = $user->toggleFavoriteBook($book->id);

            return $status === 'detached'
            ? 'お気に入りを解除しました。'
            : 'お気に入りを追加しました。';
        });

        return back()->with('success', $message);
    }
}

