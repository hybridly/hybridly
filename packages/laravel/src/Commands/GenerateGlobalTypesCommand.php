<?php

namespace Hybridly\Commands;

use Hybridly\Actions\GeneratePhpTypesAction;
use Hybridly\Actions\GenerateRoutesDefinitionsAction;
use Hybridly\Configuration\Configuration;
use Illuminate\Console\Command;
use Spatie\TypeScriptTransformer\Structures\TransformedType;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;

final class GenerateGlobalTypesCommand extends Command
{
    protected $signature = 'hybridly:types {--allow-failures}';
    protected $description = 'Generates the global front-end type definitions.';
    protected $hidden = true;

    public function handle(
        TypeScriptTransformerConfig $typeScriptTransformerConfig,
        Configuration $config,
        GeneratePhpTypesAction $generate_php_types,
        GenerateRoutesDefinitionsAction $generate_routes_definitions,
    ): int {
        try {
            $generate_routes_definitions($config);
            $collection = $generate_php_types($typeScriptTransformerConfig);
        } catch (\Throwable $exception) {
            if ($this->option('allow-failures')) {
                return self::SUCCESS;
            }

            throw $exception;
        }

        if ($this->output->isVerbose()) {
            $this->table(['PHP class', 'TypeScript entity'], collect($collection)
                ->map(fn (TransformedType $type, string $class) => [
                    $class,
                    $type->getTypeScriptName(),
                ]));
        }

        $this->components->info(\sprintf(
            '%s PHP types written to <comment>%s</comment>.',
            count($collection),
            GeneratePhpTypesAction::PHP_TYPES_PATH,
        ));

        $this->components->info(\sprintf(
            'Routes written to <comment>%s</comment>.',
            GenerateRoutesDefinitionsAction::ROUTES_PATH,
        ));

        return self::SUCCESS;
    }
}
