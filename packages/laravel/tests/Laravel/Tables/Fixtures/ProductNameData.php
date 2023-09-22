<?php

namespace Hybridly\Tests\Laravel\Tables\Fixtures;

use Spatie\LaravelData\Data;

class ProductNameData extends Data
{
    public function __construct(
        public readonly string $name,
    ) {
    }
}
