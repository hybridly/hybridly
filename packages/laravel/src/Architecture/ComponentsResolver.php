<?php

namespace Hybridly\Architecture;

interface ComponentsResolver
{
    /**
     * Loads view files from the given directory and associates them to the given namespace.
     */
    public function loadViewsFrom(string $directory, null|string|array $namespace = null, ?int $depth = null): static;

    /**
     * Loads layout files from the given directory and associates them to the given namespace.
     */
    public function loadLayoutsFrom(string $directory, null|string|array $namespace = null): static;

    /**
     * Loads component files from the given directory and associates them to the given namespace.
     */
    public function loadComponentsFrom(string $directory, null|string|array $namespace = null): static;

    /**
     * Auto-import TypeScript files from the given directory.
     */
    public function loadTypeScriptFilesFrom(string $directory, bool $deep = false): static;

    /**
     * Loads a namespaced module and its views, layouts and components.
     */
    public function loadModuleFrom(
        string $directory,
        null|string|array $namespace,
        bool $deep = false,
        bool $loadViews = true,
        bool $loadLayouts = true,
        bool $loadComponents = true,
        bool $loadTypeScript = true,
    ): static;

    /**
     * Loads all modules in the given directory.
     */
    public function loadModulesFrom(string $directory, bool $deep = false): void;

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
     * Gets namespaced layouts files.
     *
     * @return array<{path: string, identifier: string}>
     */
    public function getComponents(): array;

    /**
     * Gets directories from which TypeScript files should be loaded.
     *
     * @return string[]
     */
    public function getTypeScriptDirectories(): array;

    /**
     * Gets the file extensions to resolve.
     *
     * @return string[]
     */
    public function getExtensions(): array;

    /**
     * Unload the specified components.
     */
    public function unload(bool $views = true, bool $layouts = true, bool $components = true, bool $typeScriptDirectories = true): static;

    /**
     * Overrides the identifier generator implementation.
     */
    public function setIdentifierGenerator(IdentifierGenerator $identifierGenerator): static;
}
