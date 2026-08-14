<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use App\Models\Book;
use App\Models\ReadingPlan;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReadingPlanController extends Controller
{
    use AuthorizesRequests;

    /**
     * 読書計画一覧画面の表示（ステータス絞り込み ＆ 日付・Enumトランスフォーム対応）
     *
     * @param  Request  $request  ステータスフィルタ（status）を含むリクエスト
     * @return View 読書計画一覧画面のビュー
     */
    public function index(Request $request): View
    {
        $currentStatus = $request->input('status');

        $readingPlans = ReadingPlan::with('book')
            ->where('user_id', Auth::id())
            ->when($currentStatus && ReadingPlanStatus::tryFrom($currentStatus), function ($query) use ($currentStatus) {
                $query->where('status', $currentStatus);
            })
            ->latest('target_date')
            ->get()
            ->transform(function (ReadingPlan $plan) {
                $plan->status = is_string($plan->status) ? ReadingPlanStatus::tryFrom($plan->status) : $plan->status;
                $plan->target_date = is_string($plan->target_date) ? Carbon::parse($plan->target_date) : $plan->target_date;
                $plan->completed_at = is_string($plan->completed_at) ? Carbon::parse($plan->completed_at) : $plan->completed_at;

                return $plan;
            });

        return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    /**
     * 新規読書計画作成画面の表示
     *
     * @return View 読書計画作成画面のビュー
     */
    public function create(): View
    {
        $books = Book::all();

        return view('reading-plans.create', compact('books'));

    }

    /**
     * 新しい読書計画の登録処理（初期ステータス: 未読）
     *
     * @param  StoreReadingPlanRequest  $request  バリデーション済みのリクエスト
     * @return RedirectResponse 読書計画一覧画面へのリダイレクトレスポンス
     */
    public function store(StoreReadingPlanRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        ReadingPlan::create([
            'user_id' => Auth::id(),
            'book_id' => $validated['book_id'],
            'target_date' => $validated['target_date'],
            'status' => ReadingPlanStatus::Unread,
        ]);

        return redirect()->route('reading-plans.index')
            ->with('success', '新しい読書計画を作成しました。');
    }

    /**
     * 読書計画編集画面の表示（作成者本人であることのポリシー認可制限付き）
     *
     * @param string $id 読書計画の主キーID（文字列型）
     * @return \Illuminate\View\View 読書計画編集画面のビュー
     * @throws \Illuminate\Auth\Access\AuthorizationException 策定者本人ではないユーザーがアクセスした場合
     */
    public function edit(string $id): View
    {
        $readingPlan = ReadingPlan::findOrFail($id);

        $this->authorize('view', $readingPlan);

        $readingPlan->load('book');

        return view('reading-plans.edit', compact('readingPlan'));
    }

    /**
     * 読書計画の目標期日更新処理（作成者本人であることのポリシー認可制限付き）
     *
     * @param \App\Http\Requests\UpdateReadingPlanRequest $request 入力バリデーション済みのリクエスト
     * @param string $id 読書計画の主キーID（文字列型）
     * @return \Illuminate\Http\RedirectResponse 読書計画一覧画面へのリダイレクトレスポンス
     * @throws \Illuminate\Auth\Access\AuthorizationException 策定者本人ではないユーザーが更新を試みた場合
     */
    public function update(UpdateReadingPlanRequest $request, string $id): RedirectResponse
    {
        $readingPlan = ReadingPlan::findOrFail($id);

        $this->authorize('update', $readingPlan);

        $validated = $request->validated();

        $readingPlan->update([
            'target_date' => $validated['target_date'],
        ]);

        return redirect()->route('reading-plans.index')
            ->with('success', '読書計画の期日を更新しました。');
    }

    /**
     * 読書計画の削除処理（作成者本人であることのポリシー認可制限付き）
     *
     * @param string $id 読書計画の主キーID（文字列型）
     * @return \Illuminate\Http\RedirectResponse 読書計画一覧画面へのリダイレクトレスポンス
     * @throws \Illuminate\Auth\Access\AuthorizationException 策定者本人ではないユーザーが削除を試みた場合
     */
    public function destroy(string $id): RedirectResponse
    {
        $readingPlan = ReadingPlan::findOrFail($id);

        $this->authorize('delete', $readingPlan);

        $readingPlan->delete();

        return redirect()->route('reading-plans.index')
            ->with('success', '読書計画を削除しました。');
    }

    /**
     * 読書計画の読了確定処理（ステータスを読了に変更し、完了日時を自動記録）
     *
     * @param  int|string  $id  読書計画の主キーID
     * @return RedirectResponse 読書計画一覧画面へのリダイレクトレスポンス
     */
    public function complete(string $id): RedirectResponse
    {
        $readingPlan = ReadingPlan::findOrFail($id);

        $this->authorize('complete', $readingPlan);

        $readingPlan->update([
            'status' => ReadingPlanStatus::Completed,
            'completed_at' => Carbon::now(),
        ]);

        return redirect()->route('reading-plans.index')
            ->with('success', '書籍を読了しました。');

    }
}
