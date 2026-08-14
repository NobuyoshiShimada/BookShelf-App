<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenreRequest;
use App\Models\Genre;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class GenreController extends Controller
{
    /**
     * 登録されているジャンルの一覧画面を表示（関連書籍数を内包し、名称順にソート）
     *
     * @return View ジャンル一覧画面のビュー
     */
    public function index(): View
    {
        $genres = Genre::withCount('books')->oldest('name')->get();

        return view('genres.index', compact('genres'));
    }

    /**
     * 新規ジャンル登録画面の表示
     *
     * @return View ジャンル新規作成画面のビュー
     */
    public function create(): View
    {
        return view('genres.create');
    }

    /**
     * 新しいジャンルのデータベース登録処理
     *
     * @param  GenreRequest  $request  入力バリデーション済みのリクエスト
     * @return RedirectResponse ジャンル一覧画面へのリダイレクトレスポンス
     */
    public function store(GenreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $genre = Genre::create([
            'name' => $validated['name'],
        ]);

        return redirect()->route('genres.index')
            ->with('success', 'ジャンル「'.$genre->name.'」を新しく登録しました。');
    }

    /**
     * 特定のジャンルに紐づく書籍一覧詳細画面の表示（10件ページネーション対応）
     *
     * @param  Genre  $genre  ルートモデルバインディングされたジャンルモデル
     * @return View ジャンル詳細画面のビュー
     */
    public function show(Genre $genre): View
    {
        $books = $genre->books()->paginate(10);

        return view('genres.show', compact('genre', 'books'));
    }

    /**
     * ジャンル情報の編集画面の表示
     *
     * @param  Genre  $genre  ルートモデルバインディングされたジャンルモデル
     * @return View ジャンル編集画面のビュー
     */
    public function edit(Genre $genre): View
    {
        return view('genres.edit', compact('genre'));
    }

    /**
     * 既存のジャンル情報の更新処理
     *
     * @param  GenreRequest  $request  入力バリデーション済みのリクエスト
     * @param  Genre  $genre  ルートモデルバインディングされたジャンルモデル
     * @return RedirectResponse ジャンル一覧画面へのリダイレクトレスポンス
     */
    public function update(GenreRequest $request, Genre $genre): RedirectResponse
    {
        $validated = $request->validated();

        $genre->update([
            'name' => $validated['name'],
        ]);

        return redirect()->route('genres.index', $genre)
            ->with('success', 'ジャンル「'.$genre->name.'」の情報を更新しました。');

    }

    /**
     * ジャンルの削除処理（関連書籍との中間テーブル紐付け解除をトランザクション内で処理）
     *
     * @param  Genre  $genre  ルートモデルバインディングされたジャンルモデル
     * @return RedirectResponse ジャンル一覧画面へのリダイレクトレスポンス
     */
    public function destroy(Genre $genre): RedirectResponse
    {
        DB::transaction(function () use ($genre) {
            $genre->books()->sync([]);
            $genre->delete();
        });

        return redirect()->route('genres.index')
            ->with('success', 'ジャンル「'.$genre->name.'」を削除しました。');
    }
}
