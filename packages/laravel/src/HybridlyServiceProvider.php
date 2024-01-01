<?php

namespace Hybridly;

use Hybridly\Commands\GenerateGlobalTypesCommand;
use Hybridly\Commands\I18nCommand;
use Hybridly\Commands\InstallCommand;
use Hybridly\Commands\MakeTableCommand;
use Hybridly\Commands\PrintConfigurationCommand;
use Hybridly\Http\Controller;
use Hybridly\Support\Configuration\Configuration;
use Hybridly\Support\Data\PartialLazy;
use Hybridly\Support\RayDumper;
use Hybridly\Support\Version;
use Hybridly\Tables\Actions\Http\InvokedActionController;
use Hybridly\Testing\TestResponseMacros;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Foundation\Vite;
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
        // Registers the application directory so the root view can be loaded
        $this->callAfterResolving('view', function (Factory $view): void {
            $view->addLocation(base_path(\dirname($this->getConfiguration()->architecture->getApplicationMainPath())));
        });

        // Loads the default module if enabled
        if (Configuration::get()->architecture->loadDefaultModule) {
            $this->app->make(Hybridly::class)->loadModuleFrom(
                directory: base_path($this->getConfiguration()->architecture->rootDirectory),
                namespace: 'default',
            );
        }
    }

    protected function registerBindings(): void
    {
        $this->app->singleton(Hybridly::class);
        $this->app->singleton(Configuration::class, fn ($app) => Configuration::fromArray($app['config']['hybridly'] ?? []));
    }

    protected function registerDirectives(): void
    {
        // Registers @hybridly
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

        // Overrides @vite so we don't have to specify the path to the
        // application entry point in multiple files
        $this->app->afterResolving('blade.compiler', function (BladeCompiler $compiler) {
            $compiler->directive('vite', fn (?string $expression = null) => sprintf(
                '<?php echo app(%s::class)(%s); ?>',
                Vite::class,
                $expression ?: '"' . $this->getConfiguration()->architecture->getApplicationMainPath() . '"',
            ));
        });
    }

    protected function registerMacros(): void
    {
        /** Checks if the request is hybrid. */
        Request::macro('isHybrid', fn () => is_hybrid());

        /** Checks if the request is partial. */
        Request::macro('isPartial', fn () => is_partial());

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
        AboutCommand::add('Hybridly', fn () => [
            'Version (composer)' => Version::getPrettyComposerVersion(),
            'Version (npm)' => Version::getPrettyNpmVersion(),
            'Application main' => $this->getConfiguration()->architecture->getApplicationMainPath(),
            'Extensions' => implode(', ', $this->getConfiguration()->architecture->extensions),
            'Eager view loading' => $this->getConfiguration()->architecture->eagerLoadViews
                ? '<fg=yellow;options=bold>ENABLED</>'
                : '<fg=yellow;options=bold>DISABLED</>',
            'Architecture' => $this->getConfiguration()->architecture->loadDefaultModule
                ? '<fg=green;options=bold>DEFAULT</>'
                : '<fg=blue;options=bold>CUSTOM</>',
        ]);
    }

    protected function registerActionsEndpoint(): void
    {
        if (!$this->getConfiguration()->tables->enableActions) {
            return;
        }

        if (!($this->app instanceof CachesRoutes && $this->app->routesAreCached())) {
            Route::post($this->getConfiguration()->tables->actionsEndpoint, InvokedActionController::class)
                ->middleware($this->getConfiguration()->tables->actionsEndpointMiddleware)
                ->name($this->getConfiguration()->tables->actionsEndpointName);
        }
    }

    private function getConfiguration(): Configuration
    {
        return $this->app->make(Configuration::class);
    }
}
