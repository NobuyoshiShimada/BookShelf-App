<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\ReadingPlan;
use App\Enums\ReadingPlanStatus;
use App\Http\Requests\StoreReadingPlanRequest;
use App\Http\Requests\UpdateReadingPlanRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ReadingPlanController extends Controller
{
    use AuthorizesRequests;

    // 読書計画一覧の表示
    public function index(Request $request)
    {
        $currentStatus = $request->input('status');

        $query = ReadingPlan::with('book')
        ->where('user_id', Auth::id());

        if ($currentStatus && ReadingPlanStatus::tryFrom($currentStatus))
            {
                $query->where('status', $currentStatus);
            }

            $readingPlans = $query->latest('target_date')->get();

            $readingPlans->transform(function ($plan) {
            // status がただの文字列なら、Enumオブジェクトに強制変換
            if (is_string($plan->status)) {
                $plan->status = ReadingPlanStatus::tryFrom($plan->status);
            }

            // target_date がただの文字列なら、Carbonインスタンスに強制変換
            if ($plan->target_date && is_string($plan->target_date)) {
                $plan->target_date = Carbon::parse($plan->target_date);
            }

            // completed_at がただの文字列なら、Carbonインスタンスに強制変換
            if ($plan->completed_at && is_string($plan->completed_at)) {
                $plan->completed_at = Carbon::parse($plan->completed_at);
            }

            return $plan;
        });

            return view('reading-plans.index', compact('readingPlans', 'currentStatus'));
    }

    // 新規読書計画作成画面の表示
    public function create()
    {
        $books = Book::all();

        return view('reading-plans.create', compact('books'));

    }

    // 新規読書計画の登録処理
    public function store(StoreReadingPlanRequest $request)
    {
        $validated = $request->validated();

        ReadingPlan::create([
            'user_id' => \Illuminate\Support\Facades\Auth::id(),
            'book_id' => $validated['book_id'],
            'target_date' => $validated['target_date'],
            'status' => ReadingPlanStatus::Unread,
        ]);

        return redirect()->route('reading-plans.index')
        ->with('success', '新しい読書計画を作成しました。');
    }

    // 読書計画編集画面の表示
    public function edit($id)
    {
        $readingPlan = ReadingPlan::findOrFail($id);

        $this->authorize('view', $readingPlan);

            $readingPlan->load('book');

            return view('reading-plans.edit', compact('readingPlan'));
    }

    // 読書計画の更新処理
    public function update(UpdateReadingPlanRequest $request, $id)
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

    // 読書計画の削除処理
    public function destroy ($id)
    {
        $readingPlan = ReadingPlan::findOrFail($id);

        $this->authorize('delete', $readingPlan);

        if ($readingPlan->user_id !== Auth::id()) {
            abort(403);
        }

        $readingPlan->delete();

        return redirect()->route('reading-plans.index')
        ->with('success', '読書計画を削除しました。');
    }

    // 読書計画の読了処理
    public function complete($id)
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
