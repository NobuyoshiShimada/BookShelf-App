<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\GenreController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
// 公開ページ
// 書籍一覧
Route::get('/books', [BookController::class, 'index'])->name('books.index');
// 書籍のランキング
Route::get('/ranking', [BookController::class, 'ranking'])->name('ranking.index');

// 認証済み
Route::middleware(['auth'])->group(function () {
    // 新規書籍登録
    Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
    // 新規書籍登録処理
    Route::post('/books', [BookController::class, 'store'])->name('books.store');
    // 書籍編集
    Route::get('/books/{book}/edit', [BookController::class, 'edit'])->name('books.edit');
    // 書籍更新
    Route::put('/books/{book}', [BookController::class, 'update'])->name('books.update');
    // 書籍削除
    Route::delete('/books/{book}', [BookController::class, 'destroy'])->name('books.destroy');
    // 書籍のお気に入り一覧
    Route::get('/favorites', [FavoriteController::class, 'index'])->name('favorites.index');
    // お気に入りボタンを押す
    Route::post('/books/{book}/favorites', [FavoriteController::class, 'toggle'])->name('favorites.toggle');

    // レビューの新規投稿
    Route::post('/books/{book}/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    // レビューのいいねボタンを押す
    Route::post('/books/{book}/reviews/like', [ReviewController::class, 'toggle'])->name('reviews.like');
    // レビューの編集
    Route::get('/reviews/{review}/edit', [ReviewController::class, 'edit'])->name('reviews.edit');
    // レビューの更新
    Route::put('/reviews/{review}', [ReviewController::class, 'update'])->name('reviews.update');
    // レビューの削除
    Route::delete('/reviews/{review}', [ReviewController::class, 'destroy'])->name('reviews.destroy');

    // ジャンル一覧
    Route::get('/genres', [GenreController::class, 'index'])->name('genres.index');
    // 新ジャンル登録
    Route::get('/genres/create', [GenreController::class, 'create'])->name('genres.create');
    // ジャンル詳細
    Route::get('/genres/{genre}', [GenreController::class, 'show'])->name('genres.show');
    // 新ジャンル登録処理
    Route::post('/genres', [GenreController::class, 'store'])->name('genres.store');
    // ジャンル編集
    Route::get('/genres/{genre}/edit', [GenreController::class, 'edit'])->name('genres.edit');
    // ジャンル更新
    Route::put('/genres/{genre}', [GenreController::class, 'update'])->name('genres.update');
    // ジャンル削除
    Route::delete('/genres/{genre}', [GenreController::class, 'destroy'])->name('genres.destroy');

});

// 公開ページ
// 書籍詳細
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');
