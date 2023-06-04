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
     * Gets the view finder.
     */
    public function getViewFinder(): VueViewFinder
    {
        return $this->finder;
    }

    public function useDomains(string $directoryName = 'domains'): void
    {
        $root = config('hybridly.architecture.root', 'resources');
        $directory = base_path($root . '/' . $directoryName);

        foreach (scandir($directory) as $domain) {
            if (\in_array($domain, ['.', '..'], true)) {
                continue;
            }

            $base = $directory . '/' . $domain;

            rescue(fn () => $this->getViewFinder()->loadDirectory($base), report: false);
            rescue(fn () => $this->getViewFinder()->loadViewsFrom($base . '/pages', $domain), report: false);
            rescue(fn () => $this->getViewFinder()->loadLayoutsFrom($base . '/layouts', $domain), report: false);
            rescue(fn () => $this->getViewFinder()->loadComponentsFrom($base . '/components', $domain), report: false);
        }
    }
}
