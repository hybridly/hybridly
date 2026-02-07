<?php

namespace Hybridly\Tests\Fixtures\Database;

use Illuminate\Database\Eloquent\Factories\Factory;

class BookFactory extends Factory
{
    protected $model = Book::class;

    public function definition()
    {
        return [
            'title' => fake()->words(3, true),
            'author_id' => null,
            'description' => fake()->realText(),
            'pages' => fake()->numberBetween(100, 1000),
            'published_at' => fake()->dateTimeBetween('-5 years', 'now'),
        ];
    }
}
