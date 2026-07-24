<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

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

    public function toggle(Book $book)
    {
        /** @var User */
        $user = Auth::user();

        $user->favoriteBooks()->toggle($book->id);

        return back()->with('success', 'お気に入りを追加しました。');
    }
}
