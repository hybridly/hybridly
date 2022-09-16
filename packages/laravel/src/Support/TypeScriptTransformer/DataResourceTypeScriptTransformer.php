<?php

namespace Monolikit\Support\TypeScriptTransformer;

use ReflectionClass;
use ReflectionProperty;
use Spatie\LaravelData\Support\TypeScriptTransformer\DataTypeScriptTransformer as BaseDataTypeScriptTransformer;
use Spatie\TypeScriptTransformer\Structures\MissingSymbolsCollection;

class DataResourceTypeScriptTransformer extends BaseDataTypeScriptTransformer
{
    protected function resolveProperties(ReflectionClass $class): array
    {
        $properties = array_filter(
            $class->getProperties(ReflectionProperty::IS_PUBLIC),
            fn (ReflectionProperty $property) => !$property->isStatic() && $property->getName() !== 'authorization',
        );

        return array_values($properties);
    }

    protected function transformExtra(
        ReflectionClass $class,
        MissingSymbolsCollection $missingSymbols,
    ): string {
        $authorizations = $class->getProperty('authorizations')->getValue();

        if (\count($authorizations) === 0) {
            return '';
        }

        $type = 'authorization: { ';
        foreach ($authorizations as $action) {
            $type .= "{$action}: boolean; ";
        }

        return "{$type}}\n";
    }
}
