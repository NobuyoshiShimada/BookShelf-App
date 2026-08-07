<?php

namespace App\Http\Controllers;

use App\Enums\ReadingPlanStatus;
use App\Models\ReadingPlan;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReadingReportController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // 基本設計
        // 総レビュー数
        $totalReviews = Review::where('user_id', $userId)->count();

        // 読了件数
        $booksRead = ReadingPlan::where('user_id', $userId)
            ->where('status', ReadingPlanStatus::Completed->value)
            ->count();

        // 平均評価
        $averageRating = Review::where('user_id', $userId)->avg('rating');
        $averageRating = $averageRating ? (float) $averageRating : 0;

        // 評価分布
        $ratingDistribution = collect([0 => 0, 1 => 0, 2 => 0, 3 => 0, 4 => 0]);

        $reviewCounts = Review::where('user_id', $userId)
            ->select('rating', DB::raw('count(*) as total'))
            ->groupBy('rating')
            ->get();

        foreach ($reviewCounts as $rc) {
            if ($rc->rating >= 1 && $rc->rating <= 5) {
                $ratingDistribution[$rc->rating - 1] = $rc->total;
            }
        }

        // 高評価書籍TOP5
        $topRatedBooks = Review::with('book')
            ->where('user_id', $userId)
            ->where('rating', '>=', 4)
            ->orderByDesc('rating')
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(function ($review) {
                return [
                    'id' => $review->book->id ?? null,
                    'title' => $review->book->title ?? '不明な書籍',
                    'author' => $review->book->author ?? '不明な著者',
                    'rating' => $review->rating,
                ];
            })
            ->filter(fn ($item) => ! is_null($item['id']))
            ->values()
            ->toArray();

        // ジャンル別評価傾向TOP5
        $genreRatings = DB::table('reviews')
            ->join('book_genre', 'reviews.book_id', '=', 'book_genre.book_id')
            ->join('genres', 'book_genre.genre_id', '=', 'genres.id')
            ->where('reviews.user_id', $userId)
            ->select(
                'genres.id',
                'genres.name',
                DB::raw('count(reviews.id) as count'),
                DB::raw('avg(reviews.rating) as average_rating'),
            )
            ->groupBy('genres.id', 'genres.name')
            ->orderByDesc('average_rating')
            ->orderByDesc('count')
            ->take(5)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'count' => $item->count,
                    'average_rating' => (float) $item->average_rating,
                ];
            })
            ->toArray();

        // ビューへ渡す連想配列
        $stats = [
            'summary' => [
                'total_reviews' => $totalReviews,
                'books_read' => $booksRead,
                'average_rating' => $averageRating,
            ],
            'rating_distribution' => $ratingDistribution,
            'top_rated_books' => $topRatedBooks,
            'genre_ratings' => $genreRatings,
        ];

        return view('reports.index', compact('stats'));
    }
}
