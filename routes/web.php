<?php

use App\Http\Controllers\BookController;
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
Route::get('/books', [BookController::class, 'index'])->name('books.index');
Route::get('/ranking', [BookController::class, 'index'])->name('ranking.index');
Route::get('/books/{book}', [BookController::class, 'show'])->name('books.show');

// 認証済み
Route::middleware(['auth'])->group(function () {
    Route::get('/books/create', [BookController::class, 'create'])->name('books.create');
});
