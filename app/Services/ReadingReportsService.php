<?php

namespace App\Services;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\Review;
use App\Models\User;
use Illuminate\Support\Collection;

class ReadingReportsService
{
    /**
     * 指定されたユーザーの読書統計・ランキングデータを生成して返却
     *
     * @param  \App\Models\User  $user  対象ユーザーのインスタンス
     * @return array<string, mixed> 統計情報を構造化した連想配列
     */
    public function generateUserStats(User $user): array
    {
        $reviews = Review::with(['book.genres', 'book.user'])
            ->where('user_id', $user->id)
            ->get();

        $completedPlans = ReadingPlan::where('user_id', $user->id)
            ->where('status', ReadingPlanStatus::Completed->value)
            ->get();

        // 1. 基本統計の算出
        $totalReviews = $reviews->count();
        $totalCompleted = $completedPlans->count();
        $averageRating = number_format($reviews->avg('rating') ?? 0.0, 1);

        // 2. 評価分布の集計
        $ratingGroup = $reviews->groupBy('rating');
        $ratingDistribution = collect([0, 1, 2, 3, 4])
            ->mapWithKeys(function (int $index) use ($ratingGroup) {
                $star = $index + 1;
                return [$index => $ratingGroup->get($star, collect())->count()];
            });

        // 3. 高評価書籍TOP5の抽出
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

        // 4. ジャンル別評価傾向TOP5の多次元集計
        $genreRatings = $reviews->flatMap(fn (Review $r) => collect($r->book->genres ?? [])->map(fn ($genre) => [
            'id' => $genre->id,
            'genre_name' => $genre->name,
            'rating' => $r->rating,
        ]))
            ->groupBy('genre_name')
            ->map(function (Collection $genreReviews, string $name) {
                $firstItem = $genreReviews->first();
                $genreId   = $firstItem['id'] ?? null;

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

        // 構造化した統計データの返却
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
