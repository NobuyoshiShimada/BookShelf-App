<?php

namespace App\Http\Controllers;

use App\Http\Requests\BookRequest;
use App\Models\Book;
use App\Models\Genre;
use App\Services\GoogleBooksService;
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

        $books = Book::withAvg('reviews', 'rating')
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
                    'rating' => $query->orderBy('reviews_avg_rating', 'desc')->latest(),
                    'title'  => $query->orderBy('title', 'asc'),
                    default  => $query->latest(),
                };
            })
            ->with(['genres'])
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
     * 新規書籍のデータベース登録処理
     */
    public function store(BookRequest $request): RedirectResponse
    {
        // 💡 永続化とリレーション同期はモデルの createWithGenres に丸投げ
        $book = Book::createWithGenres(
            array_merge($request->validated(), ['user_id' => Auth::id()]),
            $request->input('genres', [])
        );

        return redirect()->route('books.index')
            ->with('success', "書籍「{$book->title}」を新しく登録しました。");
    }

    /**
     * 書籍詳細画面の表示（関連レビューやいいね情報の遅延ロード対応）
     *
     * @param \App\Models\Book $book ルートモデルバインディングされた書籍モデルインスタンス
     * @return \Illuminate\View\View 書籍詳細画面のビュー
     */
    public function show(Book $book): View
    {
        $book->loadMissing(['genres', 'favoriteBooks', 'reviews.likedByUsers']);

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
     * 既存の書籍情報の更新処理
     */
    public function update(BookRequest $request, Book $book): RedirectResponse
    {
        $this->authorize('update', $book);

        // 💡 リレーション同期を含む一括更新ロジックをモデルへ完全委譲
        $book->updateWithGenres(
            $request->validated(),
            $request->input('genres', [])
        );

        return redirect()->route('books.show', $book)
            ->with('success', "書籍「{$book->title}」の情報を更新しました。");
    }

    /**
     * 書籍データの削除処理
     *
     * @param \App\Models\Book $book
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Book $book): RedirectResponse
    {
        $this->authorize('delete', $book);

        $book->purgeFully();

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
        ->has('reviews')
        ->withCount('reviews')
        ->withAvg('reviews', 'rating')
        ->orderBy('reviews_avg_rating', 'desc')
        ->orderBy('reviews_count', 'desc')
        ->take(10)
        ->get();

        return view('ranking.index', compact('rankedBooks'));
    }

    /**
     * ISBNコードによる書籍情報の非同期自動取得API
     */
    public function searchIsbn(string $isbn, GoogleBooksService $googleBooksService): JsonResponse
    {
        // 13桁の数字チェック
        if (! preg_match('/^[0-9]{13}$/', $isbn)) {
            return response()->json([
                'error' => 'ISBNは13桁の数字で入力してください。'], 400);
        }

        try {
            $bookDate = $googleBooksService->fetchByIsbn($isbn);
            return response()->json($bookDate);
        } catch(\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 404);
        }
    }
}
