<?php

use App\Http\Controllers\Api\V1\BookController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
 Route::prefix('v1')->group(function () {
     Route::apiResource('books', BookController::class);
 });

// // 書籍一覧
// Route::get('/v1/books', [BookController::class, 'index']);

// // 書籍新規登録
// Route::post('/v1/books', [BookController::class, 'store']);

// // 書籍詳細
// Route::get('/v1/books/{book}', [BookController::class, 'show']);

// // 書籍更新
// Route::put('/v1/books/{book}', [BookController::class, 'update']);

// // 書籍削除
// Route::delete('/v1/books/{book}', [BookController::class, 'destroy']);

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });
