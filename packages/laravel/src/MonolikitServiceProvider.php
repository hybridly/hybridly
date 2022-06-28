<?php

namespace Monolikit;

use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\View\Compilers\BladeCompiler;
use Monolikit\Commands\InstallCommand;
use Monolikit\Http\Controller;
use Monolikit\PropertiesResolver\PropertiesResolver;
use Monolikit\PropertiesResolver\RequestPropertiesResolver;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MonolikitServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('monolikit')
            ->hasConfigFile()
            ->hasCommand(InstallCommand::class);
    }
    
    public function registeringPackage(): void
    {
        $this->registerBindings();
        $this->registerDirectives();
        $this->registerMacros();
    }
    
    protected function registerMacros(): void
    {
        /** Checks if the request is monolikit. */
        Request::macro('monolikit', fn () => monolikit()->isMonolikit());
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
}
