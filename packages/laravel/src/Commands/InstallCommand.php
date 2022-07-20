<?php

namespace Monolikit\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Input\InputOption;

class InstallCommand extends GeneratorCommand
{
    protected $name = 'monolikit:install';
    protected $description = 'Installs Monolikit.';
    protected $type = 'Monolikit middleware';
    protected $hidden = true;

    public function handle()
    {
        parent::handle();

        $this->installMiddlewareAfter(
            'SubstituteBindings::class',
            sprintf('\\%s\\%s::class', $this->getDefaultNamespace(trim($this->rootNamespace(), '\\')), $this->argument('name')),
        );

        Artisan::call('vendor:publish --tag=monolikit-config');
    }

    protected function getStub()
    {
        return __DIR__ . '/../../stubs/middleware.php.stub';
    }

    protected function installMiddlewareAfter($after, $name, $group = 'web')
    {
        $httpKernel = file_get_contents(app_path('Http/Kernel.php'));

        $middlewareGroups = str($httpKernel)->betweenFirst('$middlewareGroups = [', '];');
        $middlewareGroup = str($middlewareGroups)->betweenFirst("'${group}' => [", '],');

        if (!str_contains($middlewareGroup, $name)) {
            $modifiedMiddlewareGroup = str_replace(
                $after . ',',
                $after . ',' . \PHP_EOL . '            ' . $name . ',',
                $middlewareGroup,
            );

            file_put_contents(app_path('Http/Kernel.php'), str_replace(
                $middlewareGroups,
                str_replace($middlewareGroup, $modifiedMiddlewareGroup, $middlewareGroups),
                $httpKernel,
            ));
        }
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\Http\Middleware';
    }

    protected function getArguments(): array
    {
        return [
            ['name', InputOption::VALUE_REQUIRED, 'Name of the Middleware that should be created', 'HandleMonolikitRequests'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the Middleware already exists'],
        ];
    }
}
