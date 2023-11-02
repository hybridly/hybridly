<?php

namespace Hybridly\Support;

use Exception;

final class VueViewFinder
{
    /** @var array<{path: string, identifier: string, namespace: string}> */
    protected array $views = [];

    /** @var array<{path: string, identifier: string, namespace: string}> */
    protected array $layouts = [];

    /** @var array<{path: string, identifier: string, namespace: string}> */
    protected array $components = [];

    /** @var string[] */
    protected array $loadedDirectories = [];

    /** @var string[] */
    protected array $extensions = [];

    public function __construct(
        private readonly \Illuminate\Config\Repository $config,
    ) {
        $this->extensions = array_map(
            callback: fn (string $extension) => ".{$extension}",
            array: $config->get('hybridly.architecture.extensions', ['vue', 'tsx']),
        );
    }

    /**
     * Loads view files from the given directory and associates them to the given namespace.
     */
    public function loadViewsFrom(string $directory, null|string|array $namespace = null): static
    {
        $this->views = array_merge($this->views, $this->loadVueFilesFrom(
            directory: $directory,
            namespace: $namespace,
        ));

        return $this;
    }

    /**
     * Loads layout files from the given directory and associates them to the given namespace.
     */
    public function loadLayoutsFrom(string $directory, null|string|array $namespace = null): static
    {
        $this->layouts = array_merge($this->layouts, $this->loadVueFilesFrom(
            directory: $directory,
            namespace: $namespace,
        ));

        return $this;
    }

    /**
     * Loads component files from the given directory and associates them to the given namespace.
     */
    public function loadComponentsFrom(string $directory, null|string|array $namespace = null): static
    {
        $this->components = array_merge($this->components, $this->loadVueFilesFrom(
            directory: $directory,
            namespace: $namespace,
        ));

        return $this;
    }

    /**
     * Loads the given directory.
     */
    public function loadDirectory(string $directory): static
    {
        $realpth = realpath($directory);

        if ($realpth === false) {
            throw new Exception("Directory [{$directory}] does not exist.");
        }

        $this->loadedDirectories[] = $this->normalizeDirectory($realpth);

        return $this;
    }

    public function isDirectoryLoaded(string $directory): bool
    {
        return \array_key_exists($this->normalizeDirectory($directory), $this->loadedDirectories);
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

    public function getLoadedDirectories(): array
    {
        return $this->loadedDirectories;
    }

    /**
     * @return array<{path: string, identifier: string}>
     */
    protected function loadVueFilesFrom(string $directory, null|string|array $namespace = null): array
    {
        $this->loadDirectory($directory);

        $namespace = \is_array($namespace) ? implode('-', $namespace) : $namespace;
        $namespace = str($namespace ?? 'default')->basename()->kebab();

        return $this->findVueFiles(
            directory: $directory,
            baseDirectory: $directory,
            namespace: $namespace,
        );
    }

    /**
     * @return array<{path: string, identifier: string}>
     */
    protected function findVueFiles(string $directory, string $baseDirectory, string $namespace): array
    {
        $files = [];

        foreach (scandir($directory) as $file) {
            if (\in_array($file, ['.', '..'], true)) {
                continue;
            }

            $path = $directory . '/' . $file;

            if (is_dir($path)) {
                $files = array_merge($files, $this->findVueFiles($path, $baseDirectory, $namespace));
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
        return str($path)
            ->after($baseDirectory)
            ->ltrim('/\\')
            ->replace(['/', '\\'], '.')
            ->replace($this->extensions, '')
            ->when($namespace !== 'default')
            ->prepend("{$namespace}::");
    }

    protected function normalizeDirectory(string $directory): string
    {
        return str_replace(base_path('/'), '', $directory);
    }
}
