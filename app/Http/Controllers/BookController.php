<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookRequest;
use App\Models\Book;
use App\Models\Genre;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;

class BookController extends Controller
{
    use AuthorizesRequests;

    /**
     * 書籍一覧画面の表示（検索・フィルタ・ソート・ページネーション対応）
     *
     * @param \Illuminate\Http\Request $request 検索キーワード、ジャンルID、ソートキーを含むリクエスト
     * @return \Illuminate\View\View 書籍一覧画面のビュー
     */
    public function index(Request $request): View
    {
        $genres = Genre::all();

        $books = Book::with('genres')
        ->withAvg('reviews', 'rating')
        ->withCount('reviews')
        ->when($request->filled('keyword'), function ($query) use ($request) {
            $keyword = '%' . $request->input('keyword') . '%';
            $query->where(function ($q) use ($keyword){
                $q->where('title', 'like', $keyword)
                ->orWhere('author', 'like', $keyword);
            });
        })
        ->when($request->filled('genre'), function ($query) use ($request) {
            $query->whereHas('genres', function ($q) use ($request) {
                $q->where('genres.id', $request->input('genre'));
            });
        })
        ->when($request->input('sort', 'newest'), function ($query, $sort){
            match ($sort) {
                'oldest' => $query->oldest(),
                'rating' => $query->orderByRaw('reviews_avg_rating IS NULL ASC, reviews_avg_rating DESC')->latest(),
                'title'  => $query->orderBy('title', 'asc'),
                default  => $query->latest(),
            };
        })
        ->paginate(10)
        ->appends($request->query());

        return view('books.index', compact('books', 'genres'));
    }

    /**
     * 新規書籍登録画面の表示
     *
     * @return \Illuminate\View\View 新規書籍登録画面のビュー
     */
    public function create(): View
    {
        $genres = Genre::all();

        return view('books.create', compact('genres'));
    }

    /**
     * 新規書籍のデータベース登録処理（関連ジャンルとの同期を内包）
     *
     * @param \App\Http\Requests\BookRequest $request バリデーション検証済みのリクエストオブジェクト
     * @return \Illuminate\Http\RedirectResponse 書籍一覧画面へのリダイレクトレスポンス
     */
    public function store(BookRequest $request): RedirectResponse
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
     * 書籍詳細画面の表示（関連レビューやいいね情報の遅延ロード対応）
     *
     * @param \App\Models\Book $book ルートモデルバインディングされた書籍モデルインスタンス
     * @return \Illuminate\View\View 書籍詳細画面のビュー
     */
    public function show(Book $book): View
    {
        $book->load(['genres', 'favoriteBooks', 'reviews.likedByUsers']);

        return view('books.show', compact('book'));
    }

    /**
     * 書籍編集画面の表示（登録者本人であることのポリシー認可制限付き）
     *
     * @param \App\Models\Book $book ルートモデルバインディングされた書籍モデルインスタンス
     * @return \Illuminate\View\View 書籍編集画面のビュー
     * @throws \Illuminate\Auth\Access\AuthorizationException 登録者本人ではないユーザーがアクセスした場合
     */
    public function edit(Book $book): View
    {
        $this->authorize('update', $book);

        $genres = Genre::all();
        $book->load('genres');

        return view('books.edit', compact('book', 'genres'));

    }

    /**
     * 既存の書籍情報の更新処理（登録者本人であることのポリシー認可制限付き）
     *
     * @param \App\Http\Requests\BookRequest $request バリデーション検証済みのリクエストオブジェクト
     * @param \App\Models\Book $book 操作対象の書籍モデルインスタンス
     * @return \Illuminate\Http\RedirectResponse 書籍詳細画面へのリダイレクトレスポンス
     * @throws \Illuminate\Auth\Access\AuthorizationException 登録者本人ではないユーザーがアクセスした場合
     */
    public function update(BookRequest $request, Book $book): RedirectResponse
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
     * 書籍データの削除処理（関連子データもトランザクション内で連動削除）
     *
     * @param \App\Models\Book $book 操作対象の書籍モデルインスタンス
     * @return \Illuminate\Http\RedirectResponse 書籍一覧画面へのリダイレクトレスポンス
     * @throws \Illuminate\Auth\Access\AuthorizationException 登録者本人ではないユーザーがアクセスした場合
     */
    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        DB::transaction(function () use ($book) {
            $book->genres()->sync([]);
            $book->reviews()->delete();
            $book->delete();
        });

        return redirect()->route('books.index')
            ->with('success', '書籍「'.$book->title.'」をデータベースから完全に削除しました。');
    }

    /**
     * 総合評価ランキングTOP10画面の表示
     *
     * @return \Illuminate\View\View 総合ランキング画面のビュー
     */
    public function ranking(): View
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

    /**
     * Google Books API を使用した、ISBNコードによる書籍情報の非同期自動取得
     *
     * @param string $isbn 13桁のISBNコード文字列
     * @return \Illuminate\Http\JsonResponse 取得に成功した書籍データ、またはエラーメッセージのJSON
     */
    public function searchIsbn(string $isbn): JsonResponse
    {
        // 13桁の数字チェック
        if (! preg_match('/^[0-9]{13}$/', $isbn)) {
            return response()->json([
                'error' => 'ISBNは13桁の数字で入力してください。'], 400);
        }

        // Google Book APIへの問い合わせ
        try {
            $response = Http::withoutVerifying()
                ->timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
                ])
                ->get('https://www.googleapis.com/books/v1/volumes', [
                    'q' => 'isbn:'.$isbn,
                    'key' => env('GOOGLE_BOOKS_API_KEY'),
                ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Googleサーバーへの接続に失敗しました: '.$e->getMessage()], 500);
        }

        if ($response->failed()) {
            return response()->json(['error' => 'Google APIからエラーが返されました (ステータスコード: '.$response->status().')'], 500);
        }

        $data = $response->json();

        // 該当する書籍が見つからない時
        if (! isset($data['items'][0]['volumeInfo'])) {
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

        return response()->json([
            'title' => $volumeInfo['title'] ?? '',
            'author' => isset($volumeInfo['authors']) ? implode(', ', $volumeInfo['authors']) : '',
            'published_date' => $publishedDate,
            'description' => $volumeInfo['description'] ?? '',
            'image_url' => $imageUrl,
        ]);
    }
}
