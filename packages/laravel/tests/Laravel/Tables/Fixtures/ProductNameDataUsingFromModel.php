<?php

namespace Hybridly\Tests\Laravel\Tables\Fixtures;

use Carbon\CarbonInterface;
use Hybridly\Support\Data\DataResource;
use Hybridly\Tests\Fixtures\Database\Product;

class ProductNameDataUsingFromModel extends DataResource
{
    protected static array $authorizations = [
        'returns-true',
        'returns-false',
    ];

    public function __construct(
        public readonly string $name,
        public readonly CarbonInterface $created_at,
    ) {
    }

    public static function fromModel(Product $product): static
    {
        return new static(
            name: $product->name,
            created_at: $product->created_at,
        );
    }
}
