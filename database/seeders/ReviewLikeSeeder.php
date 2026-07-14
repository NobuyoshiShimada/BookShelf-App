<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();
        $reviews = Review::all();

        foreach ($reviews as $review) {
            $otherUserIds = $users->where('id', '!=', $review->user_id)
            ->pluck('id')
            ->toArray();

            shuffle($otherUserIds);
            $likeCount = rand(0, 3);
            $userIdsToLike = array_slice($otherUserIds, 0, $likeCount);

            $review->likedByUsers()->syncWithoutDetaching($userIdsToLike);
        }
    }
}
