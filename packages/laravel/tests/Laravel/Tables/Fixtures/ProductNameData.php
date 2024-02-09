<?php

namespace Hybridly\Tests\Laravel\Tables\Fixtures;

use Hybridly\Support\Data\DataResource;

class ProductNameData extends DataResource
{
    protected static array $authorizations = [
        'returns-true',
        'returns-false',
    ];

    public function __construct(
        public readonly string $name,
    ) {
    }
}
