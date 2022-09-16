<?php

namespace Monolikit\Support\TypeScriptTransformer;

use Monolikit\Support\Data\DataResourceContract;
use ReflectionClass;
use Spatie\LaravelData\Contracts\BaseData;
use Spatie\LaravelData\Support\TypeScriptTransformer\DataTypeScriptTransformer;
use Spatie\TypeScriptTransformer\Collectors\Collector;
use Spatie\TypeScriptTransformer\Structures\TransformedType;

class DataResourceTypeScriptCollector extends Collector
{
    public function getTransformedType(ReflectionClass $class): ?TransformedType
    {
        if (!$class->isSubclassOf(BaseData::class)) {
            return null;
        }

        $transformer = $class->isSubclassOf(DataResourceContract::class)
            ? new DataResourceTypeScriptTransformer($this->config)
            : new DataTypeScriptTransformer($this->config);

        return $transformer->transform($class, $class->getShortName());
    }
}
