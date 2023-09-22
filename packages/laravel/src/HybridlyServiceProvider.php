<?php

namespace Hybridly;

use Hybridly\Commands\GenerateGlobalTypesCommand;
use Hybridly\Commands\I18nCommand;
use Hybridly\Commands\InstallCommand;
use Hybridly\Commands\MakeTableCommand;
use Hybridly\Commands\PrintConfigurationCommand;
use Hybridly\Http\Controller;
use Hybridly\Support\Data\PartialLazy;
use Hybridly\Support\RayDumper;
use Hybridly\Support\Version;
use Hybridly\Tables\Actions\Http\InvokedActionController;
use Hybridly\Testing\TestResponseMacros;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Factory;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class HybridlyServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('hybridly')
            ->hasConfigFile()
            ->hasCommand(InstallCommand::class)
            ->hasCommand(I18nCommand::class)
            ->hasCommand(PrintConfigurationCommand::class)
            ->hasCommand(MakeTableCommand::class)
            ->hasCommand(GenerateGlobalTypesCommand::class);
    }

    public function registeringPackage(): void
    {
        $this->registerBindings();
        $this->registerDirectives();
        $this->registerMacros();
        $this->registerTestingMacros();
        $this->registerArchitecture();
        $this->registerAbout();

        $this->callAfterResolving('view', function (Factory $view): void {
            $view->addLocation($this->getRootPath('application'));
        });
    }

    public function bootingPackage(): void
    {
        $this->registerActionsEndpoint();
    }

    public function packageBooted(): void
    {
        if (class_exists(\Spatie\LaravelData\Lazy::class)) {
            \Spatie\LaravelData\Lazy::macro('partial', function (\Closure $value): PartialLazy {
                return new PartialLazy($value);
            });
        }

        if (class_exists(\Spatie\LaravelRay\Ray::class)) {
            $this->app->singleton(RayDumper::class);
            $dumper = $this->app->get(RayDumper::class);

            \Spatie\LaravelRay\Ray::macro('showHybridRequests', function () use ($dumper) {
                $dumper->showHybridRequests();
            });

            \Spatie\LaravelRay\Ray::macro('stopShowingHybridRequests', function () use ($dumper) {
                $dumper->stopShowingHybridRequests();
            });
        }
    }

    protected function registerArchitecture(): void
    {
        $preset = config('hybridly.architecture.preset', 'default');
        $domainsDirectory = config('hybridly.architecture.domains_directory', 'domains');

        match ($preset) {
            'default' => $this->app->make(Hybridly::class)->loadModuleFrom($this->getRootPath(), 'default'),
            'modules' => $this->app->make(Hybridly::class)->loadModulesFrom($this->getRootPath($domainsDirectory)),
            default => null
        };
    }

    protected function registerBindings(): void
    {
        $this->app->singleton(Hybridly::class);
    }

    protected function registerDirectives(): void
    {
        $this->callAfterResolving('blade.compiler', function (BladeCompiler $blade) {
            $blade->directive('hybridly', function ($expression = '') {
                $options = str($expression)
                    ->matchAll('/(?:class|id|element): [\'"][\w -]+[\'"] *,?/')
                    ->flatMap(function ($e) {
                        preg_match('/([\w -]+): *[\'"]([\w -]+)[\'"]/', $e, $matches);

                        return [$matches[1] => $matches[2]];
                    });

                $element = $options->get('element', 'div');
                $id = $options->get('id', 'root');
                $class = $options->get('class', '');
                $template = <<<HTML
                    <{$element} id="{$id}" class="{$class}" data-payload="{{ json_encode(\$payload) }}"></{$element}>
                HTML;

                return implode(' ', array_map('trim', explode("\n", $template)));
            });
        });
    }

    protected function registerMacros(): void
    {
        /** Checks if the request is hybridly. */
        Request::macro('isHybrid', fn () => hybridly()->isHybrid());

        /** Serves a hybrid route. */
        Router::macro('hybridly', function (string $uri, string $component, array $properties = []) {
            /** @phpstan-ignore-next-line */
            return $this->match(['GET', 'HEAD'], $uri, Controller::class)
                ->defaults('component', $component)
                ->defaults('properties', $properties);
        });
    }

    protected function registerTestingMacros(): void
    {
        TestResponse::mixin(new TestResponseMacros());
    }

    protected function registerAbout(): void
    {
        AboutCommand::add('hybridly', fn () => [
            'Version (composer)' => Version::getPrettyComposerVersion(),
            'Version (npm)' => Version::getPrettyNpmVersion(),
            'Eager view loading' => config('hybridly.architecture.eager_load_views') ? '<fg=yellow;options=bold>ENABLED</>' : 'OFF',
            'Root' => $this->getRootPath(),
            'Architecture' => match (config('hybridly.architecture.preset', 'default')) {
                'modules' => 'module-based',
                default => 'classic Laravel'
            },
        ]);
    }

    protected function registerActionsEndpoint(): void
    {
        if (config('hybridly.tables.enable_actions') === false) {
            return;
        }

        if (!($this->app instanceof CachesRoutes && $this->app->routesAreCached())) {
            Route::post(config('hybridly.tables.actions_endpoint'), InvokedActionController::class)
                ->middleware(config('hybridly.tables.actions_endpoint_middleware', []))
                ->name(config('hybridly.tables.actions_endpoint_name'));
        }
    }

    private function getRootPath(...$segments): string
    {
        return base_path(implode(\DIRECTORY_SEPARATOR, [
            config('hybridly.architecture.root', 'resources'),
            ...$segments ?? [],
        ]));
    }
}
