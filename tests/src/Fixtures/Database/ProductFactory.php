<?php

namespace Hybridly\Tests\Fixtures\Database;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            'name' => $this->faker->words(3, true),
            'vendor' => $this->faker->company(),
            'description' => $this->faker->realText(),
            'price' => $this->faker->numberBetween(1, 10000),
            'stock_count' => $this->faker->numberBetween(1, 1000),
            'is_active' => $this->faker->boolean(90),
            'published_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
