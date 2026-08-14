<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreBookRequest;
use App\Http\Requests\Api\V1\UpdateBookRequest;
use App\Http\Resources\Api\V1\BookResource;
use App\Models\Book;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Auth;

class BookController extends Controller
{
    /**
     * 書籍一覧データの取得 (API仕様: ページネーション ＆ 動的検索対応)
     *     *
     * @param  Request  $request  キーワード、ジャンルID、表示件数を含むリクエストオブジェクト
     * @return AnonymousResourceCollection ページネーション付き書籍リソースのコレクション
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $books = Book::with('genres')
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $keyword = '%' . $request->input('keyword') . '%';
                $query->where(function ($q) use ($keyword) {
                    $q->where('title', 'like', $keyword)
                    ->orWhere('author', 'like', $keyword)
                    ->orWhere('description', 'like', $keyword)
                    ->orWhere('isbn', 'like', $keyword);
                });
        })
        ->when($request->filled('genre_id'), function ($query) use ($request) {
                $query->whereHas('genres', function ($q) use ($request) {
                    $q->where('genres.id', $request->input('genre_id'));
                });
            })
            ->latest()
            ->paginate(
                min((int) $request->input('per_page', 10), 100)
            );
        return BookResource::collection($books);
    }

    /**
     * 新しい書籍データの認証登録処理
     *
     * リクエスト内からの user_id 改ざんを徹底防衛。
     * トークン認証された本人のID (Auth::id()) をサーバー側で強制割り当てします。
     *
     * @param  StoreBookRequest  $request  バリデーションルールを通過した書籍登録データ
     * @return JsonResponse ステータスコード 201 Created を内包するレスポンス
     */
    public function store(StoreBookRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $book = Book::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'],
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
        ]);

        collect($request->input('genres'))->whenNotEmpty(fn ($genres) => $book->genres()->sync($genres));

        return (new BookResource($book->load('genres')))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 特定の書籍の検索・詳細情報取得
     *
     * @param  Book  $book  ルートモデルバインディングによって自動引き直しされた書籍モデル
     * @return BookResource 単一書籍の詳細APIリソース
     */
    public function show(Book $book): BookResource
    {
        $book->load(['genres', 'user', 'reviews' => function ($query) {
            $query->with('user')->withCount('likedByUsers');
        }]);

        $book->loadCount('reviews');
        $book->loadAvg('reviews', 'rating');

        return new BookResource($book);
    }

    /**
     * 既存書籍情報の認証更新処理 (所有者限定ガード付き)
     *
     * @param  UpdateBookRequest  $request  バリデーションルールを通過した更新データ
     * @param  Book  $book  操作対象の書籍モデル
     * @return BookResource|JsonResponse 更新成功時はリソース、認可失敗時は403 JSON
     */
    public function update(UpdateBookRequest $request, Book $book): BookResource|JsonResponse
    {
        try {
            $this->authorize('update', $book);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => '自分が登録した書籍情報のみ更新できます。',
            ], 403);
        }

        $validated = $request->validated();

        $book->update([
            'title' => $validated['title'],
            'author' => $validated['author'],
            'isbn' => $validated['isbn'],
            'published_date' => $validated['published_date'],
            'description' => $validated['description'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
        ]);

        if ($request->has('genres')) {
            $book->genres()->sync($request->genres);
        }

        return new BookResource($book->load('genres'));
    }

    /**
     * 書籍データの完全削除処理 (所有者限定ガード付き)
     *
     * @param  Book  $book  操作対象の書籍モデル
     * @return JsonResponse 削除完了メッセージ、または認可失敗時は403 JSON
     */
    public function destroy(Book $book): JsonResponse
    {
        try {
            $this->authorize('delete', $book);
        } catch (AuthorizationException $e) {
            return response()->json([
                'message' => '自分が登録した書籍のみ削除できます。',
            ], 403);
        }

        $book->genres()->sync([]);
        $book->reviews()->delete();
        $book->delete();

        return response()->json([
            'message' => '書籍情報を削除しました。',
        ], 200);
    }
}
