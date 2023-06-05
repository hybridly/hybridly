<?php

namespace Hybridly\ViewFinder;

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

    /**
     * Loads view files from the given directory and associates them to the given namespace.
     */
    public function loadViewsFrom(string $directory, ?string $namespace = null): static
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
    public function loadLayoutsFrom(string $directory, ?string $namespace = null): static
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
    public function loadComponentsFrom(string $directory, ?string $namespace = null): static
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
        $directory = realpath($directory);

        if ($directory === false) {
            throw new Exception("Directory [{$directory}] does not exist.");
        }

        $this->loadedDirectories[] = $this->normalizeDirectory($directory);

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
    protected function loadVueFilesFrom(string $directory, ?string $namespace = null): array
    {
        $this->loadDirectory($directory);

        return $this->findVueFiles(
            directory: $directory,
            baseDirectory: $directory,
            namespace: $namespace ?? 'default',
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

            $path = $directory . \DIRECTORY_SEPARATOR . $file;

            if (is_dir($path)) {
                $files = array_merge($files, $this->findVueFiles($path, $baseDirectory, $namespace));
            } else {
                if (str_ends_with($path, '.vue')) {
                    $files[] = [
                        'namespace' => $namespace,
                        'path' => str_replace(base_path('/'), '', $path),
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
            ->replace('.vue', '')
            ->kebab()
            ->when($namespace !== 'default')
            ->prepend("{$namespace}::");
    }

    protected function normalizeDirectory(string $directory): string
    {
        return str_replace(base_path('/'), '', $directory);
    }
}
