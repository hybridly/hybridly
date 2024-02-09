<?php

namespace Hybridly\Tests\Laravel\Tables\Fixtures;

use Carbon\CarbonInterface;
use Hybridly\Support\Data\DataResource;

class ProductNameData extends DataResource
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
}
