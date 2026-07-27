<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\BookResource;
use App\Models\Book;
use Illuminate\Http\Request;
use App\Http\Requests\Api\V1\StoreBookRequest;
use App\Http\Requests\Api\V1\UpdateBookRequest;

class BookController extends Controller
{
    public function index(Request $request)
    {
        $query = Book::with('genres')
        ->withCount('reviews')
        ->withAvg('reviews', 'rating');

        if ($request->filled('keyword')) {
            $keyword = '%' . $request->input('keyword') . '%';
            $query->where(function($q) use ($keyword) {
                $q->where('title', 'like', $keyword)
                ->orWhere('author','like', $keyword)
                ->orWhere('description', 'like', $keyword)
                ->orWhere('isbn', 'like', $keyword);
            });
        }

        if ($request->filled('genre_id')) {
            $genreId = $request->input('genre_id');
            $query->whereHas('genres', function ($q) use ($genreId) {
                $q->where('genres.id', $genreId);
            });
        }

        $perPage = (int) $request->input('per_page', 10);
        $perPage = min($perPage, 100);
        $books = $query->latest()->paginate($perPage);

        return BookResource::collection($books);
    }

    /**
     * Store a newly created resource in storage.
     */
    // 書籍新規登録
    public function store(StoreBookRequest $request)
    {
        $validated = $request->validated();

        $book = Book::create([
            // 認証不要用の固定ユーザーID割り当て
            'user_id' => 1,
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'],
            'description' => $validated['description'],
            'image_url' => $validated['image_url'],
        ]);

        if ($request->has('genres')) {
            $book->genres()->sync($request->genres);
        }

        return (new BookResource($book->load('genres')))
        ->response()
        ->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    // 書籍詳細
    public function show(Book $book)
    {
        $book->load(['genres', 'user', 'reviews' => function($query) {
            $query->with('user')->withCount('likedByUsers');
        }]);

        $book->loadCount('reviews');
        $book->loadAvg('reviews', 'rating');

        return new BookResource($book);
    }

    /**
     * Update the specified resource in storage.
     */
    // 書籍更新
    public function update(UpdateBookRequest $request, Book $book)
    {
        $validated = $request->validated();

        $book->update([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'],
            'description' => $validated['description'],
            'image_url' => $validated['image_url'],
        ]);

        if ($request->has('genres')) {
            $book->genres()->sync($request->genres);
        }

        return new BookResource($book->load('genres'));
    }

    /**
     * Remove the specified resource from storage.
     */
    // 書籍削除
    public function destroy(Book $book)
    {
        $book->genres()->sync([]);
        $book->reviews()->delete();
        $book->delete();

        return response()->json([
            'message' => '書籍情報を削除しました。'
        ], 200);
    }
}
