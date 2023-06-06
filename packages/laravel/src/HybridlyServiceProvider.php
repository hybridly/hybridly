<?php

namespace Hybridly;

use Hybridly\Commands\GenerateGlobalTypesCommand;
use Hybridly\Commands\I18nCommand;
use Hybridly\Commands\InstallCommand;
use Hybridly\Commands\PrintConfigurationCommand;
use Hybridly\Http\Controller;
use Hybridly\Support\Data\PartialLazy;
use Hybridly\Testing\TestResponseMacros;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Testing\TestResponse;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Factory;
use Spatie\LaravelData\Lazy;
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
            ->hasCommand(GenerateGlobalTypesCommand::class);
    }

    public function registeringPackage(): void
    {
        $this->registerBindings();
        $this->registerDirectives();
        $this->registerMacros();
        $this->registerTestingMacros();
        $this->registerArchitecture();

        $this->callAfterResolving('view', static function (Factory $view): void {
            $view->addLocation(resource_path('application'));
        });
    }

    protected function registerArchitecture(): void
    {
        $preset = $this->app['config']->get('hybridly.architecture.preset', 'default');
        $domainsDirectory = $this->app['config']->get('hybridly.architecture.domains_directory', 'domains');

        match ($preset) {
            'default' => $this->app->make(Hybridly::class)->loadModuleFrom(resource_path(), 'default'),
            'modules' => $this->app->make(Hybridly::class)->loadModulesFrom(resource_path($domainsDirectory)),
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

        if (class_exists(\Spatie\LaravelData\Lazy::class)) {
            Lazy::macro('partial', function (\Closure $value): PartialLazy {
                return new PartialLazy($value);
            });
        }
    }

    protected function registerTestingMacros(): void
    {
        TestResponse::mixin(new TestResponseMacros());
    }
}
