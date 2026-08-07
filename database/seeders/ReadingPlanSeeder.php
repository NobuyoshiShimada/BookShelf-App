<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use App\Models\ReadingPlan;
use App\Enums\ReadingPlanStatus;
use Illuminate\Support\Carbon;
use Illuminate\Database\Seeder;

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

        if (!$testUser) {
            $testUser = User::first() ?? User::factory()->create();
        }

        $today = Carbon::now();

        $patterns = [
            [
                'label' => '① 6日前（超過・進行中）',
                'status' => ReadingPlanStatus::Reading,
                'target_date' => $today->copy()->subDays(6),
                'completed_at' => null,
            ],
            [
                'label' => '② 3日前（超過・未読）',
                'status' => ReadingPlanStatus::Unread,
                'target_date' => $today->copy()->subDays(3),
                'completed_at' => null,
            ],
            [
                'label' => '③ 当日（本日が期日・進行中）',
                'status' => ReadingPlanStatus::Reading,
                'target_date' => $today->copy(),
                'completed_at' => null,
            ],
            [
                'label' => '④ 3日後（間近の期日・未読）',
                'status' => ReadingPlanStatus::Unread,
                'target_date' => $today->copy()->addDays(3),
                'completed_at' => null,
            ],
            [
                'label' => '⑤ 6日後（余裕のある期日・進行中）',
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

        foreach ($patterns as $index => $pattern) {
            ReadingPlan::create([
                'user_id'      => $testUser->id,
                'book_id'      => $books[$index]->id,
                'target_date'  => $pattern['target_date'],
                'status'       => $pattern['status']->value,
                'completed_at' => $pattern['completed_at'],
            ]);
        }
    }
}
