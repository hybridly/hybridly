<?php

namespace Hybridly\Concerns;

use Hybridly\ViewFinder\VueViewFinder;

/** @mixin \Hybridly\Hybridly */
trait HasViewFinder
{
    /**
     * Loads view files from the given directory and associates them to the given namespace.
     */
    public function loadViewsFrom(string $directory, ?string $namespace = null): static
    {
        $this->finder->loadViewsFrom($directory, $namespace);

        return $this;
    }

    /**
     * Loads layout files from the given directory and associates them to the given namespace.
     */
    public function loadLayoutsFrom(string $directory, ?string $namespace = null): static
    {
        $this->finder->loadLayoutsFrom($directory, $namespace);

        return $this;
    }

    /**
     * Loads component files from the given directory and associates them to the given namespace.
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
     * Loads a namespaced module and its pages, layouts and components.
     */
    public function loadModuleFrom(string $directory, ?string $namespace = null): static
    {
        if ($this->getViewFinder()->isDirectoryLoaded($directory)) {
            return $this;
        }

        $namespace ??= basename($directory);
        $pagesDirectory = config('hybridly.architecture.pages_directory', 'pages');
        $layoutsDirectory = config('hybridly.architecture.layouts_directory', 'layouts');
        $componentsDirectory = config('hybridly.architecture.components_directory', 'components');

        $this->getViewFinder()->loadDirectory($directory);

        rescue(fn () => $this->getViewFinder()->loadViewsFrom($directory . '/' . $pagesDirectory, $namespace), report: false);
        rescue(fn () => $this->getViewFinder()->loadLayoutsFrom($directory . '/' . $layoutsDirectory, $namespace), report: false);
        rescue(fn () => $this->getViewFinder()->loadComponentsFrom($directory . '/' . $componentsDirectory, $namespace), report: false);

        return $this;
    }

    /**
     * Loads all modules in the given directory.
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
