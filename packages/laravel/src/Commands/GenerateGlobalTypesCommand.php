<?php

namespace Hybridly\Commands;

use Illuminate\Console\Command;
use Spatie\TypeScriptTransformer\Structures\TransformedType;
use Spatie\TypeScriptTransformer\TypeScriptTransformer;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;

class GenerateGlobalTypesCommand extends Command
{
    protected const PHP_TYPES_PATH = '.hybridly/php-types.d.ts';

    protected $signature = 'hybridly:types {--allow-failures}';
    protected $description = 'Generates the global types definitions for the front-end.';
    protected $hidden = true;

    protected int $exitCode = self::SUCCESS;

    public function handle(TypeScriptTransformerConfig $typeScriptTransformerConfig): int
    {
        try {
            $this->writePhpTypes($typeScriptTransformerConfig);
        } catch (\Throwable $exception) {
            if ($this->option('allow-failures')) {
                return self::SUCCESS;
            }

            throw $exception;
        }

        return $this->exitCode;
    }

    /**
     * Converts PHP types to TypeScript types.
     */
    protected function writePhpTypes(TypeScriptTransformerConfig $config): void
    {
        if (! class_exists(TypeScriptTransformer::class)) {
            return;
        }

        $config->outputFile(base_path(self::PHP_TYPES_PATH));
        $collection = (new TypeScriptTransformer($config))->transform();

        if ($this->output->isVerbose()) {
            $this->table(
                ['PHP class', 'TypeScript entity'],
                collect($collection)
                    ->map(fn (TransformedType $type, string $class) => [
                        $class,
                        $type->getTypeScriptName(),
                    ]),
            );
        }

        $this->components->info(\sprintf(
            '%s PHP types written to <comment>%s</comment>.',
            $collection->count(),
            self::PHP_TYPES_PATH,
        ));
    }
}
