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
    public function loadViewsFrom(string $directory, null|string|array $namespace = null): static
    {
        $this->finder->loadViewsFrom($directory, $namespace);

        return $this;
    }

    /**
     * Loads layout files from the given directory and associates them to the given namespace.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#loadlayoutsfrom
     */
    public function loadLayoutsFrom(string $directory, null|string|array $namespace = null): static
    {
        $this->finder->loadLayoutsFrom($directory, $namespace);

        return $this;
    }

    /**
     * Loads component files from the given directory and associates them to the given namespace.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#loadcomponentsfrom
     */
    public function loadComponentsFrom(string $directory, null|string|array $namespace = null): static
    {
        $this->finder->loadComponentsFrom($directory, $namespace);

        return $this;
    }

    /**
     * Auto-import TypeScript files from the given directory.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#loadtypescriptfilesfrom
     */
    public function loadTypeScriptFilesFrom(string $directory, bool $deep = false): static
    {
        $this->finder->loadTypeScriptFilesFrom($directory, $deep);

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
    public function loadModule(
        null|string|array $namespace = null,
        bool $deep = true,
        bool $loadViews = true,
        bool $loadLayouts = true,
        bool $loadComponents = true,
        bool $loadTypeScript = true,
    ): static {
        $trace = debug_backtrace(
            options: \DEBUG_BACKTRACE_IGNORE_ARGS,
            limit: 1,
        );

        $this->getViewFinder()->loadModuleFrom(
            directory: \dirname($trace[0]['file']),
            namespace: $namespace,
            deep: $deep,
            loadViews: $loadViews,
            loadLayouts: $loadLayouts,
            loadComponents: $loadComponents,
            loadTypeScript: $loadTypeScript,
        );

        return $this;
    }

    /**
     * Loads a namespaced module and its views, layouts and components.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#loadmodulefrom
     */
    public function loadModuleFrom(
        string $directory,
        null|string|array $namespace = null,
        bool $deep = false,
        bool $loadViews = true,
        bool $loadLayouts = true,
        bool $loadComponents = true,
        bool $loadTypeScript = true,
    ): static {
        $this->getViewFinder()->loadModuleFrom(
            directory: $directory,
            namespace: $namespace,
            deep: $deep,
            loadViews: $loadViews,
            loadLayouts: $loadLayouts,
            loadComponents: $loadComponents,
            loadTypeScript: $loadTypeScript,
        );

        return $this;
    }

    /**
     * Loads all modules in the given directory.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#loadmodulesfrom
     */
    public function loadModulesFrom(string $directory, bool $deep = false): static
    {
        $this->getViewFinder()->loadModulesFrom($directory, $deep);

        return $this;
    }
}
