<?php

namespace Hybridly\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Input\InputOption;

class InstallCommand extends GeneratorCommand
{
    protected $name = 'hybridly:install';
    protected $description = 'Installs Hybridly.';
    protected $type = 'Hybridly middleware';
    protected $hidden = true;

    public function handle()
    {
        parent::handle();

        $this->installMiddleware(
            sprintf('\\%s\\%s::class', $this->getDefaultNamespace(trim($this->rootNamespace(), '\\')), $this->argument('name')),
        );

        Artisan::call('vendor:publish --tag=hybridly-config');
    }

    protected function getStub()
    {
        return __DIR__ . '/../../stubs/middleware.php.stub';
    }

    protected function installMiddleware(string $name, string $group = 'web'): void
    {
        $app = file_get_contents(base_path('bootstrap/app.php'));

        if (str_contains($app, $name)) {
            return;
        }

        file_put_contents(base_path('bootstrap/app.php'), str_replace(
            "->withMiddleware(function (Middleware \$middleware) {\n",
            <<<PHP
            ->withMiddleware(function (Middleware \$middleware) {
                    \$middleware->appendToGroup('web', [App\\Http\\Middleware\\HandleHybridRequests::class]);
            PHP,
            $app,
        ));
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return $rootNamespace . '\Http\Middleware';
    }

    protected function getArguments(): array
    {
        return [
            ['name', InputOption::VALUE_REQUIRED, 'Name of the Middleware that should be created', 'HandleHybridRequests'],
        ];
    }

    protected function getOptions(): array
    {
        return [
            ['force', null, InputOption::VALUE_NONE, 'Create the class even if the Middleware already exists'],
        ];
    }
}
