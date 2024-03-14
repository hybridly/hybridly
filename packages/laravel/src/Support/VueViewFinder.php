<?php

namespace Hybridly\Support;

use Hybridly\Support\Configuration\Configuration;

final class VueViewFinder
{
    protected const DEFAULT_DEPTH = 20;

    /** @var array<{path: string, identifier: string, namespace: string}> */
    protected array $views = [];

    /** @var array<{path: string, identifier: string, namespace: string}> */
    protected array $layouts = [];

    /** @var array<{path: string, identifier: string, namespace: string}> */
    protected array $components = [];

    /** @var string[] */
    protected array $loadedTypeScriptDirectories = [];

    /** @var string[] */
    protected array $extensions = [];

    public function __construct(
        private readonly Configuration $configuration,
    ) {
        $this->extensions = array_map(
            callback: fn (string $extension) => ".{$extension}",
            array: $configuration->architecture->extensions,
        );
    }

    /**
     * Loads view files from the given directory and associates them to the given namespace.
     */
    public function loadViewsFrom(string $directory, null|string|array $namespace = null, ?int $depth = null): static
    {
        $this->views = array_merge($this->views, $this->findVueFiles(
            directory: $directory,
            baseDirectory: $directory,
            namespace: $namespace,
            depth: $depth,
            filter: fn (string $file) => !\in_array($file, array_merge($this->configuration->architecture->excludedViewsDirectories, [
                $this->configuration->architecture->layoutsDirectory,
                $this->configuration->architecture->componentsDirectory,
            ]), strict: true),
        ));

        return $this;
    }

    /**
     * Loads layout files from the given directory and associates them to the given namespace.
     */
    public function loadLayoutsFrom(string $directory, null|string|array $namespace = null): static
    {
        $this->layouts = array_merge($this->layouts, $this->findVueFiles(
            directory: $directory,
            baseDirectory: $directory,
            namespace: $namespace,
        ));

        return $this;
    }

    /**
     * Loads component files from the given directory and associates them to the given namespace.
     */
    public function loadComponentsFrom(string $directory, null|string|array $namespace = null): static
    {
        $this->components = array_merge($this->components, $this->findVueFiles(
            directory: $directory,
            baseDirectory: $directory,
            namespace: $namespace,
        ));

        return $this;
    }

    /**
     * Auto-import TypeScript files from the given directory.
     */
    public function loadTypeScriptFilesFrom(string $directory, bool $deep = false): static
    {
        $this->loadedTypeScriptDirectories = [
            ...$this->loadedTypeScriptDirectories,
            $deep
                ? str($directory)->finish('/**/*.ts')->toString()
                : str($directory)->finish('/*.ts')->toString(),
        ];

        return $this;
    }

    /**
     * Loads a namespaced module and its views, layouts and components.
     */
    public function loadModuleFrom(
        string $directory,
        null|string|array $namespace,
        bool $deep,
        bool $loadViews = true,
        bool $loadLayouts = true,
        bool $loadComponents = true,
        bool $loadTypeScript = true,
    ): static {
        $namespace ??= str($directory)->basename()->kebab();

        if ($loadTypeScript) {
            rescue(fn () => $this->loadTypeScriptFilesFrom($directory, $deep), report: false);
        }

        if ($loadViews) {
            rescue(fn () => $this->loadViewsFrom($deep ? $directory : ($directory . '/' . $this->configuration->architecture->viewsDirectory), $namespace), report: false);
        }

        if ($loadLayouts) {
            rescue(fn () => $this->loadLayoutsFrom($directory . '/' . $this->configuration->architecture->layoutsDirectory, $namespace), report: false);
        }

        if ($loadComponents) {
            rescue(fn () => $this->loadComponentsFrom($directory . '/' . $this->configuration->architecture->componentsDirectory, $namespace), report: false);
        }

        return $this;
    }

    /**
     * Loads all modules in the given directory.
     */
    public function loadModulesFrom(string $directory, bool $deep): void
    {
        foreach (scandir($directory) as $namespace) {
            if (\in_array($namespace, ['.', '..'], true)) {
                continue;
            }

            $this->loadModuleFrom(
                directory: $directory . '/' . $namespace,
                namespace: $namespace,
                deep: $deep,
            );
        }
    }

    /**
     * Gets namespaced view files.
     *
     * @return array<{path: string, identifier: string}>
     */
    public function getViews(): array
    {
        return $this->views;
    }

    /**
     * Determines whether the given identifier is registered as a view.
     */
    public function hasView(string $identifier): bool
    {
        return collect($this->views)->contains(function (array $view) use ($identifier) {
            return $view['identifier'] === $identifier;
        });
    }

    /**
     * Gets namespaced layouts files.
     *
     * @return array<{path: string, identifier: string}>
     */
    public function getLayouts(): array
    {
        return $this->layouts;
    }

    /**
     * Gets namespaced layouts files.
     *
     * @return array<{path: string, identifier: string}>
     */
    public function getComponents(): array
    {
        return $this->components;
    }

    /**
     * Gets directories from which TypeScript files should be loaded.
     *
     * @return string[]
     */
    public function getTypeScriptDirectories(): array
    {
        return $this->loadedTypeScriptDirectories;
    }

    /**
     * @return array<{path: string, identifier: string}>
     */
    protected function findVueFiles(string $directory, string $baseDirectory, null|string|array $namespace = null, ?int $depth = null, ?\Closure $filter = null): array
    {
        $depth ??= self::DEFAULT_DEPTH;

        if ($depth === 0) {
            return [];
        }

        $namespace = \is_array($namespace) ? implode('-', $namespace) : $namespace;
        $namespace = str($namespace ?? 'default')->basename()->kebab()->toString();
        $filter ??= fn () => true;
        $files = [];

        foreach (scandir($directory) as $file) {
            if (\in_array($file, ['.', '..'], true)) {
                continue;
            }

            if (!$filter($file, $directory)) {
                continue;
            }

            $path = $directory . '/' . $file;

            if (is_dir($path)) {
                $files = array_merge($files, $this->findVueFiles($path, $baseDirectory, $namespace, $depth - 1, $filter));
            } else {
                if (str($path)->endsWith($this->extensions)) {
                    $files[] = [
                        'namespace' => $namespace,
                        'path' => str($path)->replaceStart(base_path(), '')->ltrim('/\\')->toString(),
                        'identifier' => $this->getIdentifier($path, $baseDirectory, $namespace),
                    ];
                }
            }
        }

        return $files;
    }

    /**
     * An identifier is a dot-notated path from the base directory to the Vue file.
     */
    protected function getIdentifier(string $path, string $baseDirectory, string $namespace): string
    {
        return str(
            str($path)
                ->after($baseDirectory)
                ->ltrim('/\\')
                ->replace(['/', '\\'], '.')
                ->replace($this->extensions, '')
                ->explode('.')
                ->map(fn (string $str) => str($str)->kebab())
                ->join('.'),
        )
            ->when($namespace !== 'default')
            ->prepend("{$namespace}::");
    }
}
