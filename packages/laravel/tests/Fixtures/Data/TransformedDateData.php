<?php

namespace Hybridly\Tests\Fixtures\Data;

use Carbon\CarbonInterface;
use Spatie\LaravelData\Attributes\WithTransformer;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Transformers\DateTimeInterfaceTransformer;

final class TransformedDateData extends Data
{
    public function __construct(
        #[WithTransformer(DateTimeInterfaceTransformer::class, format: 'Y-m-d')]
        public readonly CarbonInterface $date,
    ) {}
}
