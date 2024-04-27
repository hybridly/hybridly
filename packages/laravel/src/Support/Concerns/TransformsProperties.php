<?php

namespace Hybridly\Support\Concerns;

use Hybridly\Support\Hybridable;
use Illuminate\Contracts\Support\Arrayable;
use Spatie\LaravelData\Contracts\TransformableData;

trait TransformsProperties
{
    protected function transformProperties(array|Arrayable|TransformableData $properties): array
    {
        if ($properties instanceof Hybridable) {
            $properties = $properties->toHybridArray();
        }

        if ($properties instanceof Arrayable || $properties instanceof TransformableData) {
            $properties = $properties->toArray();
        }

        return $properties;
    }
}
