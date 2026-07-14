<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        $favoritesMap = [
            1 => [1, 2, 3, 4],
            2 => [2, 5, 8],
            3 => [3, 4, 7, 10],
            4 => [1, 6, 8, 10, 11],
            5 => [3, 7, 11],
        ];

        foreach ($users as $user) {
            if (isset($favoritesMap[$user->id])) {
                $bookIds = $favoritesMap[$user->id];
                $user->favoriteBooks()->syncWithoutDetaching($bookIds);
            }
        }
    }
}
