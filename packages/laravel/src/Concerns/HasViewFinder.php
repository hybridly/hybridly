<?php

namespace Hybridly\Concerns;

use Hybridly\Support\VueViewFinder;

/** @mixin \Hybridly\Hybridly */
trait HasViewFinder
{
    /**
     * Loads view files from the given directory and associates them to the given namespace.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#loadviewsfrom
     */
    public function loadViewsFrom(string $directory, ?string $namespace = null): static
    {
        $this->finder->loadViewsFrom($directory, $namespace);

        return $this;
    }

    /**
     * Loads layout files from the given directory and associates them to the given namespace.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#loadlayoutsfrom
     */
    public function loadLayoutsFrom(string $directory, ?string $namespace = null): static
    {
        $this->finder->loadLayoutsFrom($directory, $namespace);

        return $this;
    }

    /**
     * Loads component files from the given directory and associates them to the given namespace.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#loadcomponentsfrom
     */
    public function loadComponentsFrom(string $directory, ?string $namespace = null): static
    {
        $this->finder->loadComponentsFrom($directory, $namespace);

        return $this;
    }

    /**
     * Gets the view finder.
     */
    public function getViewFinder(): VueViewFinder
    {
        return $this->finder;
    }

    /**
     * Loads a namespaced module and its views, layouts and components, in the current directory.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#loadmodule
     */
    public function loadModule(null|string|array $namespace = null): static
    {
        $trace = debug_backtrace(
            options: \DEBUG_BACKTRACE_IGNORE_ARGS,
            limit: 1,
        );

        return $this->loadModuleFrom(\dirname($trace[0]['file']), $namespace);
    }

    /**
     * Loads a namespaced module and its views, layouts and components.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#loadmodulefrom
     */
    public function loadModuleFrom(string $directory, null|string|array $namespace = null): static
    {
        if ($this->getViewFinder()->isDirectoryLoaded($directory)) {
            return $this;
        }

        $namespace ??= str($directory)->basename()->kebab();
        $viewsDirectory = config('hybridly.architecture.views_directory', 'views');
        $layoutsDirectory = config('hybridly.architecture.layouts_directory', 'layouts');
        $componentsDirectory = config('hybridly.architecture.components_directory', 'components');

        $this->getViewFinder()->loadDirectory($directory);

        rescue(fn () => $this->getViewFinder()->loadViewsFrom($directory . '/' . $viewsDirectory, $namespace), report: false);
        rescue(fn () => $this->getViewFinder()->loadLayoutsFrom($directory . '/' . $layoutsDirectory, $namespace), report: false);
        rescue(fn () => $this->getViewFinder()->loadComponentsFrom($directory . '/' . $componentsDirectory, $namespace), report: false);

        return $this;
    }

    /**
     * Loads all modules in the given directory.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#loadmodulesfrom
     */
    public function loadModulesFrom(string $directory): void
    {
        foreach (scandir($directory) as $namespace) {
            if (\in_array($namespace, ['.', '..'], true)) {
                continue;
            }

            $this->loadModuleFrom(
                directory: $directory . '/' . $namespace,
                namespace: $namespace,
            );
        }
    }
}
