<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class BookController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $genres = Genre::all();

        $query = Book::with('genres')
        ->withAvg('reviews', 'rating')
        ->withCount('reviews');

        // キーワード検索
        if ($request->filled('keyword')) {
            $keyword = '%' . $request->input('keyword') . '%';
            $query->where(function($q) use ($keyword) {
                $q->where('title', 'like', $keyword)
                ->orWhere('author', 'like', $keyword);
            });
        }

        // ジャンル絞り込み
        if ($request->filled('genre')) {
            $genreId = $request->input('genre');
            $query->whereHas('genres', function ($q) use ($genreId){
                $q->where('genres.id', $genreId);
            });
        }

        // ソート順
        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->oldest();
                break;
            case 'rating':
                $query->orderByRaw('reviews_avg_rating IS NULL ASC, reviews_avg_rating DESC')->latest();
                break;
            case 'title':
                $query->orderBy('title', 'asc');
                break;
            case 'newest':
                default:
                $query->latest();
                break;
        }

        $books = $query->paginate(10);
        $books->appends($request->query());

        return view('books.index', compact('books', 'genres'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(BookRequest $request)
    {
        $validated = $request->validated();

        $book = Book::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'],
            'description' => $validated['description'],
            'image_url' => $validated['image_url'],
        ]);

        $book->genres()->sync($request->genres);

        return redirect()->route('books.index')->with('success', '書籍「'.$book->title.'」を新しく登録しました。');
    }

    /**
     * Display the specified resource.
     */
    public function show(Book $book)
    {
        $book->load(['genres', 'favoriteBooks', 'reviews.likedByUsers']);

        return view('books.show', compact('book'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Book $book)
    {
        $this->authorize('update', $book);

        $genres = Genre::all();
        $book->load('genres');

        return view('books.edit', compact('book', 'genres'));

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(BookRequest $request, Book $book)
    {
        $this->authorize('update', $book);

        $validated = $request->validated();

        $book->update([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'],
            'description' => $validated['description'],
            'image_url' => $validated['image_url'],
        ]);

        $book->genres()->sync($request->genres);

        return redirect()->route('books.show', $book)
            ->with('success', '書籍「'.$book->title.'」の情報を更新しました。');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Book $book)
    {
        $this->authorize('delete', $book);

        $book->genres()->sync([]);

        $book->reviews()->delete();

        $book->delete();

        return redirect()->route('books.index')
            ->with('success', '書籍「'.$book->title.'」をデータベースから完全に削除しました。');
    }

    public function ranking()
    {
        $rankedBooks = Book::with(['genres', 'favoriteBooks'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->having('reviews_count', '>', 0)
            ->orderByDesc('reviews_avg_rating')
            ->orderByDesc('reviews_count')
            ->take(10)
            ->get();

        return view('ranking.index', compact('rankedBooks'));
    }

    // Google Books APIからISBN情報を非同期で取得してJSONで返す
    public function searchIsbn($isbn)
    {
        // 13桁の数字チェック
        if (!preg_match('/^[0-9]{13}$/', $isbn)) {
            return response()->json([
                'error' => 'ISBNは13桁の数字で入力してください。'],400);
                }

        // Google Book APIへの問い合わせ
         try {
            $response = Http::withoutVerifying()
                ->timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                ])
                ->get('https://www.googleapis.com/books/v1/volumes', [
                    'q' => 'isbn:' . $isbn,
                    'key' => env('GOOGLE_BOOKS_API_KEY'),
                ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Googleサーバーへの接続に失敗しました: ' . $e->getMessage()], 500);
        }

        if ($response->failed()) {
            return response()->json(['error' => 'Google APIからエラーが返されました (ステータスコード: ' . $response->status() . ')'], 500);
        }

        $data = $response->json();

        if ($response->failed()) {
            return response()->json([
                'error' => '外部APIとの通信に失敗しました。'
            ], 500);
        }

        $data = $response->json();

        // 該当する書籍が見つからない時
        if (!isset($data['items'][0]['volumeInfo'])) {
            return response()->json(['error' => '該当する書籍情報が見つかりませんでした。'], 404);
        }

        $volumeInfo = $data['items'][0]['volumeInfo'];


        // 出版日
        $publishedDate = $volumeInfo['publishedDate'] ?? null;

        if ($publishedDate && strlen($publishedDate) === 4) {
            $publishedDate .= '-01-01';
        } elseif ($publishedDate && strlen($publishedDate) === 7) {
            $publishedDate .= '-01';
        }

        // 画像URLの取得（サムネイルが存在する場合のみ）
        $imageUrl = $volumeInfo['imageLinks']['thumbnail'] ?? '';

        if ($imageUrl) {
            $imageUrl = str_replace('http://', 'https://', $imageUrl);
        }

        // javaScript側が期待するキーで返却
        return response()->json([
            'title' => $volumeInfo['title'] ?? '',
            'author' => isset($volumeInfo['authors']) ? implode(', ', $volumeInfo['authors']) : '',
            'published_date' => $publishedDate,
            'description' => $volumeInfo['description'] ?? '',
            'image_url' => $imageUrl,
        ]);


    }
}
