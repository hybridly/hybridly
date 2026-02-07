<?php

namespace Hybridly\Tests\Fixtures\Database;

use Illuminate\Database\Eloquent\Factories\Factory;

class AuthorFactory extends Factory
{
    protected $model = Author::class;

    public function definition()
    {
        return [
            'name' => fake()->words(2, true),
            'email' => fake()->unique()->safeEmail(),
        ];
    }
}
