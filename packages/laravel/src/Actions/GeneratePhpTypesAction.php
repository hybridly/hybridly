<?php

namespace Hybridly\Actions;

use Illuminate\Support\Collection;
use Spatie\TypeScriptTransformer\Structures\TransformedType;
use Spatie\TypeScriptTransformer\TypeScriptTransformer;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;

final class GeneratePhpTypesAction
{
    public const PHP_TYPES_PATH = '.hybridly/php-types.d.ts';

    /**
     * @return array<string, TransformedType>
     */
    public function __invoke(TypeScriptTransformerConfig $config): array
    {
        if (! class_exists(TypeScriptTransformer::class)) {
            return [];
        }

        $config->outputFile(base_path(self::PHP_TYPES_PATH));

        return Collection::make((new TypeScriptTransformer($config))->transform())->all();
    }
}
