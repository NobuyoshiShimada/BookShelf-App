<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\Genre;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class SearchVerificationSeeder extends Seeder
{
    /**
     * シーダーの実行メインロジック
     */
    public function run(): void
    {
        $users = User::all();
        $genres = Genre::all();

        if ($users->isEmpty() || $genres->isEmpty()) {
            $this->command->error('ユーザーまたはジャンルのマスターデータが空です。先にUserとGenreのシーダーを実行してください。');

            return;
        }

        $techGenreId = $genres->where('name', '技術書')->first()?->id ?? $genres->first()->id;

        $testUser = User::where('email', 'yamada@example.com')->first() ?? $users->random();

        $today = Carbon::today();

        for ($i = 1; $i <= 25; $i++) {
            $pastCreatedAt = $today->copy()->subDays(30)->subDays($i);
            $book = Book::create([
                'user_id' => $testUser->id,
                'title' => "Laravelページネーション検証 vol.{$i}",
                'author' => "解説マスター {$i}",
                'isbn' => '978400000'.str_pad($i, 4, '0', STR_PAD_LEFT),
                'published_date' => $today->copy()->subDays($i)->format('Y-m-d'),
                'description' => "ページネーションの検証用テキスト第{$i}巻です。",
                'created_at' => $pastCreatedAt,
                'updated_at' => $pastCreatedAt,
            ]);

            $book->genres()->sync([$techGenreId]);
        }
    }
}
