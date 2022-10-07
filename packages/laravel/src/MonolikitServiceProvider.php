<?php

namespace Monolikit;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Testing\TestResponse;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\FileViewFinder;
use Monolikit\Commands\I18nCommand;
use Monolikit\Commands\InstallCommand;
use Monolikit\Commands\RoutesCommand;
use Monolikit\Http\Controller;
use Monolikit\PropertiesResolver\PropertiesResolver;
use Monolikit\PropertiesResolver\RequestPropertiesResolver;
use Monolikit\Testing\TestResponseMacros;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MonolikitServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('monolikit')
            ->hasConfigFile()
            ->hasCommand(InstallCommand::class)
            ->hasCommand(I18nCommand::class)
            ->hasCommand(RoutesCommand::class);
    }

    public function registeringPackage(): void
    {
        $this->registerBindings();
        $this->registerDirectives();
        $this->registerMacros();
        $this->registerTestingMacros();

        $this->app->bind('monolikit.testing.view-finder', function ($app) {
            return new FileViewFinder(
                $app['files'],
                $app['config']->get('monolikit.testing.page_paths'),
                $app['config']->get('monolikit.testing.page_extensions'),
            );
        });
    }

    protected function registerMacros(): void
    {
        /** Checks if the request is monolikit. */
        Request::macro('isMonolikit', fn () => monolikit()->isMonolikit());
        Router::macro('monolikit', function (string $uri, string $component, array $properties = []) {
            /** @phpstan-ignore-next-line */
            return $this->match(['GET', 'HEAD'], $uri, Controller::class)
                ->defaults('component', $component)
                ->defaults('properties', $properties);
        });
    }

    protected function registerBindings(): void
    {
        $this->app->singleton(Monolikit::class);
        $this->app->bind(PropertiesResolver::class, config('monolikit.interfaces.properties_resolver', RequestPropertiesResolver::class));
    }

    protected function registerDirectives(): void
    {
        $this->callAfterResolving('blade.compiler', function (BladeCompiler $blade) {
            $blade->directive('monolikit', function ($expression = '') {
                $options = str($expression)
                    ->matchAll('/(?:class|id): [\'"][\w -]+[\'"] *,?/')
                    ->flatMap(function ($e) {
                        preg_match('/([\w -]+): *[\'"]([\w -]+)[\'"]/', $e, $matches);

                        return [$matches[1] => $matches[2]];
                    });

                $id = $options->get('id', 'root');
                $class = $options->get('class', '');
                $template = <<<HTML
                    <div id="${id}" class="${class}" data-payload="{{ json_encode(\$payload) }}"></div>
                HTML;

                return implode(' ', array_map('trim', explode("\n", $template)));
            });
        });
    }

    protected function registerTestingMacros(): void
    {
        if (class_exists(TestResponse::class)) {
            TestResponse::mixin(new TestResponseMacros());

            return;
        }
    }
}
