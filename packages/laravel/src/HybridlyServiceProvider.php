<?php

namespace Hybridly;

use Hybridly\Architecture\ComponentRepository;
use Hybridly\Architecture\JustInTimeComponentRepository;
use Hybridly\Commands\GenerateGlobalTypesCommand;
use Hybridly\Commands\I18nCommand;
use Hybridly\Commands\MakeTableCommand;
use Hybridly\Commands\PrintConfigurationCommand;
use Hybridly\Support\Configuration\Configuration;
use Hybridly\Support\Version;
use Hybridly\Tables\Actions\DataTransferObjects\BulkSelection;
use Hybridly\Tables\Actions\Http\InvokedActionController;
use Hybridly\Testing\TestResponseMacros;
use Illuminate\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Foundation\CachesRoutes;
use Illuminate\Events\Dispatcher;
use Illuminate\Foundation\Console\AboutCommand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Testing\TestResponse;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Factory;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\TaskReceived;
use Laravel\Octane\Events\TickReceived;

final class HybridlyServiceProvider extends ServiceProvider
{
    private Configuration $configuration {
        get => $this->configuration ??= $this->app->make(Configuration::class);
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            path: __DIR__ . '/../config/hybridly.php',
            key: 'hybridly',
        );

        $this->registerBindings();
        $this->registerDirectives();
        $this->registerArchitecture();
        $this->registerVersion();

        if ($this->app->runningUnitTests()) {
            $this->registerTestingMacros();
        }

        if ($this->app->runningInConsole()) {
            $this->commands([
                I18nCommand::class,
                PrintConfigurationCommand::class,
                MakeTableCommand::class,
                GenerateGlobalTypesCommand::class,
            ]);

            $this->registerAbout();
        }
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
    }

    private function registerOctaneListener(): void
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

    private function registerArchitecture(): void
    {
        // Registers the application directory so the root view can be loaded
        $this->callAfterResolving('view', function (Factory $view): void {
            $view->addLocation(base_path(\dirname($this->configuration->architecture->applicationMainPath)));
        });
    }

    private function registerBindings(): void
    {
        // The configuration is depended on in multiple,
        // places so we bind it to the container first
        $this->app->singleton(
            abstract: Configuration::class,
            concrete: fn (Application $app) => Configuration::fromArray($app->make(Repository::class)->get('hybridly', default: [])),
        );

        // The component repository is responsible for providing the components available to the front-end,
        // by default we provide a just-in-time repository that only loads components when they are
        // requested, which is at the start of the development server or during the build step
        $this->app->singleton(
            abstract: ComponentRepository::class,
            concrete: fn (Application $app) => new JustInTimeComponentRepository($app->make($this->configuration->architecture->componentLoader)),
        );

        $this->app->singleton(Hybridly::class);
    }

    private function registerDirectives(): void
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
                \Illuminate\Foundation\Vite::class,
                $expression ?: ('"' . $this->configuration->architecture->applicationMainPath . '"'),
            ));
        });
    }

    private function registerTestingMacros(): void
    {
        TestResponse::mixin(new TestResponseMacros());
    }

    private function registerAbout(): void
    {
        AboutCommand::add('Hybridly', fn () => [
            'Version (composer)' => Version::getPrettyComposerVersion(),
            'Version (npm)' => Version::getPrettyNpmVersion(),
            'Application main' => $this->configuration->architecture->applicationMainPath,
            'Eager view loading' => $this->configuration->architecture->eagerLoadViews
                ? '<fg=yellow;options=bold>ENABLED</>'
                : '<fg=yellow;options=bold>DISABLED</>',
        ]);
    }

    private function registerActionsEndpoint(): void
    {
        $this->app->bind(BulkSelection::class, fn ($app) => BulkSelection::fromRequest($app->make(Request::class)));

        if (! $this->configuration->tables->enableActions) {
            return;
        }

        if (! ($this->app instanceof CachesRoutes && $this->app->routesAreCached())) {
            Route::post($this->configuration->tables->actionsEndpoint, InvokedActionController::class)
                ->middleware($this->configuration->tables->actionsEndpointMiddleware)
                ->name($this->configuration->tables->actionsEndpointName);
        }
    }

    private function registerVersion(): void
    {
        $this->app
            ->make(Hybridly::class)
            ->resolveVersionUsing(function () {
                if (class_exists(Vite::class)) {
                    return Vite::manifestHash();
                }

                if (config('app.asset_url')) {
                    return md5(config('app.asset_url'));
                }

                if (file_exists($manifest = public_path('build/manifest.json'))) {
                    return md5_file($manifest);
                }

                if (file_exists($manifest = public_path('build/.vite/manifest.json'))) {
                    return md5_file($manifest);
                }

                return null;
            });
    }
}
