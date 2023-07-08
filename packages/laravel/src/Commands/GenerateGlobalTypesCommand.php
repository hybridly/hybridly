<?php

namespace Hybridly\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use ReflectionMethod;
use Spatie\LaravelData\Contracts\DataObject;
use Spatie\LaravelTypeScriptTransformer\Commands\TypeScriptTransformCommand;
use Spatie\StructureDiscoverer\Discover;

class GenerateGlobalTypesCommand extends Command
{
    protected $signature = 'hybridly:types';
    protected $description = 'Generates the global types definitions for the front-end.';
    protected $hidden = true;

    public function handle(): int
    {
        return $this->writeTypes()
            ? self::SUCCESS
            : self::FAILURE;
    }

    protected function getTypeDefinitions(): ?string
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
            ->get();

        if (!$class) {
            return null;
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

        if (!class_implements($data, DataObject::class)) {
            return null;
        }

        $namespace = str_replace('\\', '.', $data);

        return $this->getGlobalHybridPropertiesInterface($namespace);
    }

    protected function getGlobalHybridPropertiesInterface(?string $namespace = null): string
    {
        if ($namespace) {
            return <<<TYPESCRIPT
            /* eslint-disable */
            /* prettier-ignore */
            interface GlobalHybridlyProperties extends {$namespace} {
            }
            TYPESCRIPT;
        }

        return <<<TYPESCRIPT
        /* eslint-disable */
        /* prettier-ignore */
        type GlobalHybridlyProperties = never
        TYPESCRIPT;
    }

    protected function writeTypes(): bool
    {
        if (class_exists(TypeScriptTransformCommand::class)) {
            Artisan::call(TypeScriptTransformCommand::class, [
                '--output' => '../.hybridly/php-types.d.ts',
            ]);
        }

        $definitions = rescue(fn () => $this->getTypeDefinitions(), rescue: false, report: false);
        $result = (bool) File::put(
            $path = base_path('.hybridly/global-types.d.ts'),
            $definitions ?? $this->getGlobalHybridPropertiesInterface(),
        );

        if ($result) {
            $this->writeSuccess($path);
        }

        return $result;
    }

    protected function writeSuccess(string $path): void
    {
        $this->components->info(
            sprintf(
                "Types written to <comment>%s</comment>.",
                $path,
            ),
        );
    }
}
