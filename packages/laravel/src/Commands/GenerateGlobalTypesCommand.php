<?php

namespace Hybridly\Commands;

use Hybridly\Exceptions\CouldNotFindMiddlewareException;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionMethod;
use Spatie\LaravelData\Contracts\BaseData;
use Spatie\StructureDiscoverer\Discover;
use Spatie\TypeScriptTransformer\Structures\TransformedType;
use Spatie\TypeScriptTransformer\TypeScriptTransformer;
use Spatie\TypeScriptTransformer\TypeScriptTransformerConfig;

class GenerateGlobalTypesCommand extends Command
{
    protected const PHP_TYPES_PATH = '.hybridly/php-types.d.ts';
    protected const GLOBAL_PROPERTIES_PATH = '.hybridly/global-properties.d.ts';

    protected $signature = 'hybridly:types';
    protected $description = 'Generates the global types definitions for the front-end.';
    protected $hidden = true;

    protected int $exitCode = self::SUCCESS;

    public function handle(TypeScriptTransformerConfig $typeScriptTransformerConfig): int
    {
        $this->writePhpTypes($typeScriptTransformerConfig);
        $this->writeGlobalPropertiesInterface();

        return $this->exitCode;
    }

    /**
     * Converts PHP types to TypeScript types.
     */
    protected function writePhpTypes(TypeScriptTransformerConfig $config): void
    {
        $config->outputFile(base_path(self::PHP_TYPES_PATH));

        try {
            $collection = (new TypeScriptTransformer($config))->transform();
        } catch (\Exception $exception) {
            $this->components->error($exception->getMessage());
            $this->exitCode = self::FAILURE;

            return;
        }

        if ($this->output->isVerbose()) {
            $this->table(
                ['PHP class', 'TypeScript entity'],
                collect($collection)->map(fn (TransformedType $type, string $class) => [
                    $class,
                    $type->getTypeScriptName(),
                ]),
            );
        }

        $this->components->info(sprintf(
            '%s PHP types written to <comment>%s</comment>.',
            $collection->count(),
            self::PHP_TYPES_PATH,
        ));
    }

    /**
     * Writes the global properties interface.
     */
    protected function writeGlobalPropertiesInterface(): void
    {
        try {
            $namespace = $this->getGlobalPropertiesNamespace();
        } catch (\Exception $exception) {
            $this->components->error($exception->getMessage());
            $this->exitCode = self::FAILURE;
        }

        $namespace ??= null;

        File::put(
            base_path(self::GLOBAL_PROPERTIES_PATH),
            $this->getGlobalHybridPropertiesInterface($namespace),
        );

        $message = $namespace
            ? sprintf('Interface <comment>%s</comment>', $namespace)
            : 'Empty interface';

        $this->components->info(sprintf(
            '%s written to <comment>%s</comment>.',
            $message,
            self::GLOBAL_PROPERTIES_PATH,
        ));
    }

    /**
     * Extracts the namespace of the global properties data class used in the Hybridly middleware.
     */
    protected function getGlobalPropertiesNamespace(): ?string
    {
        $directories = array_filter([
            base_path('app'),
            base_path('src'),
        ], fn (string $directory) => File::isDirectory($directory));

        [$class] = Discover::in(...$directories)
            ->ignoreFiles(base_path('vendor'))
            ->ignoreFiles(base_path('node_modules'))
            ->ignoreFiles(base_path('resources'))
            ->classes()
            ->extending(\Hybridly\Http\Middleware::class)
            ->get() + [null];

        if (!$class) {
            throw CouldNotFindMiddlewareException::create();
        }

        $methods = (new \ReflectionClass($class))->getMethods(\ReflectionMethod::IS_PUBLIC);
        $share = collect($methods)->first(function (ReflectionMethod $method) {
            return $method->getName() === 'share';
        });

        if (!$share) {
            return null;
        }

        if (!$data = $share->getReturnType()?->getName()) {
            return null;
        }

        if (!class_exists($data)) {
            return null;
        }

        if (!class_implements($data, BaseData::class)) {
            return null;
        }

        return $data;
    }

    /**
     * Gets the global properties interface code.
     */
    protected function getGlobalHybridPropertiesInterface(?string $namespace = null): string
    {
        if ($namespace) {
            $namespace = str_replace('\\', '.', $namespace);

            return <<<JS
                /* eslint-disable */
                /* prettier-ignore */
                interface GlobalHybridlyProperties extends {$namespace} {}
            JS;
        }

        return <<<JS
            /* eslint-disable */
            /* prettier-ignore */
            type GlobalHybridlyProperties = never;
        JS;
    }
}
