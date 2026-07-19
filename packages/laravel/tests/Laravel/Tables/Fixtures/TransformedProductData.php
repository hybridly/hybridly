<?php

namespace Hybridly\Tests\Laravel\Tables\Fixtures;

use Carbon\CarbonInterface;
use Hybridly\Tests\Fixtures\Database\Product;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Transformers\DateTimeInterfaceTransformer;

final class TransformedProductData extends Data
{
    public function __construct(
        public readonly int $id,
        #[WithTransformer(DateTimeInterfaceTransformer::class, format: 'Y-m-d')]
        public readonly CarbonInterface $date,
    ) {}

    public static function fromModel(Product $product): static
    {
        return static::factory()
            ->withoutMagicalCreation()
            ->from([
                'id' => $product->id,
                'date' => $product->created_at,
            ]);
    }
}
