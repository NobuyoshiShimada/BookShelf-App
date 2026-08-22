<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use App\Services\ReadingReportService;
use App\Services\ReadingReportsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ReadingReportController extends Controller
{
    /**
     * 読書傾向・統計ダッシュボード画面の表示（受付 ➔ サービスに丸投げ ➔ 返却）
     *
     * @param  ReadingReportService  $reportService  自動解決されるレポート集計サービス
     * @return \Illuminate\Views\View 読書レポート画面のビュー
     */
    public function index(ReadingReportsService $reportService): View
    {
        $user = Auth::user();

        $stats = $reportService->generateUserStats($user);

        return view('reports.index', compact('stats'));

    }

    /**
     * 統計・ランキングデータの生成
     *
     * @param  User  $user  認証ユーザーインスタンス
     * @return array<string, mixed> 画面に渡す統計データを内包した連想配列
     */
    private function generateReportStatus(User $user): array
    {
        // 1.基本統計
        $reviews = Review::with(['book.genres', 'book.user'])
            ->where('user_id', $user->id)
            ->get();
        $completedPlans = ReadingPlan::where('user_id', $user->id)
            ->where('status', ReadingPlanStatus::Completed->value)
            ->get();

        // 総レビュー数
        $totalReviews = $reviews->count();
        // 総読了件数
        $totalCompleted = $completedPlans->count();
        // 評価の平均値
        $averageRating = number_format($reviews->avg('rating') ?? 0.0, 1);

        // 1.評価分布
        $ratingGroup = $reviews->groupBy('rating');
        $ratingDistribution = collect([0, 1, 2, 3, 4])
            ->mapWithKeys(function (int $index) use ($ratingGroup) {
                $star = $index + 1;

                return [$index => $ratingGroup->get($star, collect())->count()];
            });

        // 2.高評価書籍top5
        $topRatedBooks = $reviews->filter(fn (Review $r) => $r->rating >= 4 && isset($r->book))
            ->sortByDesc('rating')
            ->take(5)
            ->map(fn (Review $r) => [
                'id' => $r->book->id,
                'title' => $r->book->title ?? '不明な書籍',
                'author' => $r->book->author ?? '不明な著者',
                'rating' => $r->rating,
            ])
            ->values();

        // 3.ジャンル別評価傾向top5
        $genreRatings = $reviews->flatMap(fn (Review $r) => collect($r->book->genres ?? [])->map(fn ($genre) => [
            'id' => $genre->id,
            'genre_name' => $genre->name,
            'rating' => $r->rating,
        ]))
            ->groupBy('genre_name')
            ->map(function (Collection $genreReviews, string $name) {
                $firstItem = $genreReviews->first();
                $genreId = $firstItem['id'] ?? null;

                return [
                    'id' => $genreId,
                    'name' => $name,
                    'average_rating' => number_format($genreReviews->avg('rating'), 1),
                    'count' => $genreReviews->count(),
                ];
            })
            ->sortByDesc('average_rating')
            ->take(5)
            ->values()
            ->all();

        // 最終データのバインド
        return [
            'summary' => [
                'total_reviews' => $totalReviews,
                'books_read' => $totalCompleted,
                'average_rating' => $averageRating,
            ],
            'rating_distribution' => $ratingDistribution,
            'top_rated_books' => $topRatedBooks,
            'genre_ratings' => $genreRatings,
        ];
    }
}
