<?php

namespace Hybridly\Tests\Laravel\Tables\Fixtures;

use Carbon\CarbonInterface;
use Spatie\LaravelData\Data;

final class ProductNameData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly CarbonInterface $created_at,
    ) {}
}
