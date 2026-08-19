<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $books = Book::all();

        $users = User::all();

        $templates = [
            1 => '少し物足りなさを感じました。',
            2 => '理解するのに時間がかかりそうです。',
            3 => '内容は普通でした。',
            4 => 'とても分かりやすく一気に読めました。',
            5 => 'とても素晴らしい本でした。',
        ];

        foreach ($books as $book) {

            $reviewCount = rand(2, 4);

            $shuffledUsers = $users->shuffle();

            for ($i = 0; $i < $reviewCount; $i++) {
                if (! $shuffledUsers->has($i)) {
                    break;
                }

                $reviewer = $shuffledUsers->get($i);

                $rating = rand(1, 5);

                Review::create([
                    'user_id' => $reviewer->id,
                    'book_id' => $book->id,
                    'rating' => $rating,
                    'comment' => $templates[$rating],
                ]);
            }
        }
    }
}
