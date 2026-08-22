<?php

namespace Database\Seeders;

use App\Enums\ReadingPlanStatus;
use App\Models\Book;
use App\Models\ReadingPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReadingPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $books = Book::all();

        if ($books->count() < 7) {
            Book::factory()->count(7 - $books->count())->create();
            $books = Book::all();
        }

        $testUser = User::where('email', 'yamada@example.com')->first();

        if (! $testUser) {
            $testUser = User::first() ?? User::factory()->create();
        }

        $today = Carbon::now();

        $patterns = [
            [
                'label' => '① 期日の6日後（過去・読書中）',
                'status' => ReadingPlanStatus::Reading,
                'target_date' => $today->copy()->subDays(6),
                'completed_at' => null,
            ],
            [
                'label' => '② 期日の3日後（過去・読書中）',
                'status' => ReadingPlanStatus::Reading,
                'target_date' => $today->copy()->subDays(3),
                'completed_at' => null,
            ],
            [
                'label' => '③ 当日（本日が期日・読書中）',
                'status' => ReadingPlanStatus::Reading,
                'target_date' => $today->copy(),
                'completed_at' => null,
            ],
            [
                'label' => '④ 期日が3日後（未来・読書中）',
                'status' => ReadingPlanStatus::Reading,
                'target_date' => $today->copy()->addDays(3),
                'completed_at' => null,
            ],
            [
                'label' => '⑤ 期日が6日後（未来・読書中）',
                'status' => ReadingPlanStatus::Reading,
                'target_date' => $today->copy()->addDays(6),
                'completed_at' => null,
            ],
            [
                'label' => '⑥ 読了（過去に期日があり、すでに読了済）',
                'status' => ReadingPlanStatus::Completed,
                'target_date' => $today->copy()->subDays(2),
                'completed_at' => $today->copy()->subDays(2),
            ],
            [
                'label' => '⑦ 対象外/完了済み想定（すでに完了した未来の計画）',
                'status' => ReadingPlanStatus::Completed,
                'target_date' => $today->copy()->addDays(10),
                'completed_at' => $today->copy(),
            ],
        ];

        ReadingPlan::where('user_id', $testUser->id)->delete();

        foreach ($patterns as $index => $pattern) {
            ReadingPlan::create([
                'user_id' => $testUser->id,
                'book_id' => $books[$index]->id,
                'target_date' => $pattern['target_date'],
                'status' => $pattern['status']->value,
                'completed_at' => $pattern['completed_at'],
            ]);
        }
    }
}
