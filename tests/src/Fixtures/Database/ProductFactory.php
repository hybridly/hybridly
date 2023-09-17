<?php

namespace Hybridly\Tests\Fixtures\Database;

use Hybridly\Tests\Fixtures\Vendor;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition()
    {
        return [
            'name' => $this->faker->words(3, true),
            'vendor' => $this->faker->randomElement(Vendor::cases()),
            'description' => $this->faker->realText(),
            'price' => $this->faker->numberBetween(1, 10000),
            'stock_count' => $this->faker->numberBetween(1, 1000),
            'is_active' => $this->faker->boolean(90),
            'published_at' => $this->faker->dateTimeBetween('-1 year', 'now'),
        ];
    }

    public static function createImmutable(): Product
    {
        return self::new()->create([
            'name' => 'AirPods',
            'vendor' => Vendor::Apple,
            'description' => 'Nice headphones tbh',
            'price' => 250,
            'stock_count' => 42,
            'is_active' => true,
            'published_at' => '2021-01-01 00:00:00',
            'created_at' => '2021-01-01 00:00:00',
            'updated_at' => '2021-01-01 00:00:00',
        ]);
    }
}
