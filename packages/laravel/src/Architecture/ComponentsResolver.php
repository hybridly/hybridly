<?php

namespace Hybridly\Architecture;

interface ComponentsResolver
{
    /**
     * Registers a view with the given path, namespace and identifier.
     *
     * @param string $path The absolute path to the view file.
     * @param string $namespace The namespace to associate the view to.
     * @param string $identifier The identifier to associate the view to.
     */
    public function addView(string $path, string $namespace, string $identifier): static;

    /**
     * Registers a layout with the given path, namespace and identifier.
     *
     * @param string $path The absolute path to the layout file.
     * @param string $namespace The namespace to associate the layout to.
     * @param string $identifier The identifier to associate the layout to.
     */
    public function addLayout(string $path, string $namespace, string $identifier): static;

    /**
     * Loads view files from the given directory and associates them to the given namespace.
     */
    public function loadViewsFrom(string $directory, null|string|array $namespace = null, ?int $depth = null, ?\Closure $filter = null): static;

    /**
     * Loads layout files from the given directory and associates them to the given namespace.
     */
    public function loadLayoutsFrom(string $directory, null|string|array $namespace = null, ?\Closure $filter = null): static;

    /**
     * Loads a namespaced module and its views, layouts and components.
     */
    public function loadModuleFrom(string $directory, null|string|array $namespace): static;

    /**
     * Gets namespaced view files.
     *
     * @return array<{path: string, identifier: string}>
     */
    public function getViews(): array;

    /**
     * Determines whether the given identifier is registered as a view.
     */
    public function hasView(string $identifier): bool;

    /**
     * Gets namespaced layouts files.
     *
     * @return array<{path: string, identifier: string}>
     */
    public function getLayouts(): array;

    /**
     * Gets the file extensions to resolve.
     *
     * @return string[]
     */
    public function getExtensions(): array;

    /**
     * Unload the specified components.
     */
    public function unload(bool $views = true, bool $layouts = true): static;

    /**
     * Overrides the identifier generator implementation.
     */
    public function setIdentifierGenerator(IdentifierGenerator $identifierGenerator): static;
}
