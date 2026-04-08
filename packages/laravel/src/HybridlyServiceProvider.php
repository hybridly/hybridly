<?php

namespace Hybridly;

use Hybridly\Architecture\ComponentsResolver;
use Hybridly\Architecture\LazyComponentsResolver;
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
use Hybridly\Tables\Actions\DataTransferObjects\BulkSelection;
use Hybridly\Tables\Actions\Http\InvokedActionController;
use Hybridly\Testing\TestResponseMacros;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Testing\TestResponse;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Factory;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\TaskReceived;
use Laravel\Octane\Events\TickReceived;
use Spatie\LaravelData\Lazy;
use Spatie\LaravelRay\Ray;

final class HybridlyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            path: __DIR__ . '/../config/hybridly.php',
            key: 'hybridly',
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                InstallCommand::class,
                I18nCommand::class,
                PrintConfigurationCommand::class,
                MakeTableCommand::class,
                GenerateGlobalTypesCommand::class,
            ]);
        }

        $this->registerBindings();
        $this->registerDirectives();
        $this->registerTestingMacros();
        $this->registerArchitecture();
        $this->registerAbout();
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/hybridly.php' => config_path('hybridly.php'),
            ], 'hybridly-config');
        }

        $this->registerActionsEndpoint();
        $this->registerOctaneListener();

        if (class_exists(Lazy::class)) {
            Lazy::macro('partial', function (\Closure $value): PartialLazy {
                return new PartialLazy($value);
            });
        }

        if (class_exists(Ray::class)) {
            $this->app->singleton(RayDumper::class);
            $dumper = $this->app->get(RayDumper::class);

            Ray::macro('showHybridRequests', function () use ($dumper) {
                $dumper->showHybridRequests();
            });

            Ray::macro('stopShowingHybridRequests', function () use ($dumper) {
                $dumper->stopShowingHybridRequests();
            });
        }
    }

    protected function registerOctaneListener(): void
    {
        if (! class_exists(\Laravel\Octane\Octane::class)) {
            return;
        }

        foreach ([RequestReceived::class, TaskReceived::class, TickReceived::class] as $event) {
            $this->app->make(Dispatcher::class)->listen(
                $event,
                fn (RequestReceived|TaskReceived|TickReceived $event) => $event->sandbox->make(Hybridly::class)->flush(),
            );
        }
    }

    protected function registerArchitecture(): void
    {
        // Registers the application directory so the root view can be loaded
        $this->callAfterResolving('view', function (Factory $view): void {
            $view->addLocation(base_path(\dirname($this->getConfiguration()->architecture->applicationMainPath)));
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
        $this->app->singleton(Configuration::class, fn (Application $app) => Configuration::fromArray($app->make(Repository::class)->get('hybridly', default: [])));
        $this->app->singleton(ComponentsResolver::class, fn (Application $app) => new LazyComponentsResolver($app->make(Configuration::class)));
        $this->app->singleton(Hybridly::class);
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
            $compiler->directive('vite', fn (?string $expression = null) => \sprintf(
                '<?php echo app(%s::class)(%s); ?>',
                Vite::class,
                $expression ?: ('"' . $this->getConfiguration()->architecture->applicationMainPath . '"'),
            ));
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
            'Application main' => $this->getConfiguration()->architecture->applicationMainPath,
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
        $this->app->bind(BulkSelection::class, fn ($app) => BulkSelection::fromRequest($app->make(Request::class)));

        if (! $this->getConfiguration()->tables->enableActions) {
            return;
        }

        if (! ($this->app instanceof CachesRoutes && $this->app->routesAreCached())) {
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
