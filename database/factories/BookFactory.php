<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\odel=Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => fake()->realText(20) . 'の本',
            'author' => fake()->name(),
            'isbn' => fake()->isbn13(),
            'published_date' => fake()->date('Y-m-d'),
            'description' => fake()->realText(100),
            'image_url' => fake()->imageUrl(640, 480, 'books'),
        ];
    }
}
