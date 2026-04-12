<?php

namespace Hybridly\Tests\Laravel\Tables\Fixtures;

use Carbon\CarbonInterface;
use Hybridly\Tests\Fixtures\Database\Product;
use Spatie\LaravelData\Data;

final class ProductNameDataUsingFromModel extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly CarbonInterface $created_at,
    ) {}

    public static function fromModel(Product $product): static
    {
        return static::factory()
            ->withoutMagicalCreation()
            ->from([
                'name' => $product->name,
                'created_at' => $product->created_at,
            ]);
    }
}
