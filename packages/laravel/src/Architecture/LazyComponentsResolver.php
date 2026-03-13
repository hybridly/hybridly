<?php

namespace Hybridly\Architecture;

use Hybridly\Support\Configuration\Configuration;

class LazyComponentsResolver implements ComponentsResolver
{
    protected const DEFAULT_DEPTH = 5;

    /** @var array<{path: string, identifier: string, namespace: string}> */
    protected array $views = [];

    /** @var array<{path: string, identifier: string, namespace: string}> */
    protected array $layouts = [];

    /** @var string[] */
    protected array $extensions = [];

    protected IdentifierGenerator $identifierGenerator;

    public function __construct(
        private readonly Configuration $configuration,
    ) {
        $this->identifierGenerator = new KebabCaseIdentifierGenerator();
        $this->extensions = array_map(
            callback: fn (string $extension) => str($extension)->start('.')->toString(),
            array: $configuration->architecture->extensions,
        );
    }

    public function setIdentifierGenerator(IdentifierGenerator $identifierGenerator): static
    {
        $this->identifierGenerator = $identifierGenerator;

        return $this;
    }

    public function addView(string $path, string $namespace, string $identifier): static
    {
        $this->views[] = fn () => [[
            'path' => $path,
            'namespace' => $namespace,
            'identifier' => $identifier,
        ]];

        return $this;
    }

    public function addLayout(string $path, string $namespace, string $identifier): static
    {
        $this->layouts[] = fn () => [[
            'path' => $path,
            'namespace' => $namespace,
            'identifier' => $identifier,
        ]];

        return $this;
    }

    public function loadViewsFrom(string $directory, null|string|array $namespace = null, ?int $depth = null, ?\Closure $filter = null): static
    {
        $filter ??= fn (string $file) => array_any($this->extensions, fn (string $extension) => str_ends_with($file, ".view{$extension}"));

        $this->views[] = fn () => $this->findVueFiles(
            directory: $directory,
            baseDirectory: $directory,
            namespace: $namespace,
            depth: $depth,
            filter: function (string $file, string $directory) use ($filter) {
                if (! is_dir("{$directory}/{$file}") && $filter && ! $filter($file, $directory)) {
                    return false;
                }

                return true;
            },
        );

        return $this;
    }

    public function loadLayoutsFrom(string $directory, null|string|array $namespace = null, ?\Closure $filter = null): static
    {
        $filter ??= fn (string $file) => array_any($this->extensions, fn (string $extension) => str_ends_with($file, ".layout{$extension}"));

        $this->layouts[] = fn () => $this->findVueFiles(
            directory: $directory,
            baseDirectory: $directory,
            namespace: $namespace,
            filter: function (string $file, string $directory) use ($filter) {
                if (! is_dir("{$directory}/{$file}") && $filter && ! $filter($file, $directory)) {
                    return false;
                }

                return true;
            },
        );

        return $this;
    }

    public function loadModuleFrom(
        string $directory,
        null|string|array $namespace,
    ): static {
        $namespace ??= str($directory)->basename()->kebab();

        $this->loadViewsFrom($directory, $namespace);
        $this->loadLayoutsFrom($directory, $namespace);

        return $this;
    }

    public function getViews(): array
    {
        return $this->evaluateComponentCollection($this->views);
    }

    public function getLayouts(): array
    {
        return $this->evaluateComponentCollection($this->layouts);
    }

    public function getExtensions(): array
    {
        return $this->extensions;
    }

    public function hasView(string $identifier): bool
    {
        return collect($this->getViews())
            ->contains(function (array $view) use ($identifier) {
                return $view['identifier'] === $identifier;
            });
    }

    public function unload(bool $views = true, bool $layouts = true): static
    {
        if ($views) {
            $this->views = [];
        }

        if ($layouts) {
            $this->layouts = [];
        }

        return $this;
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

        if (! is_dir($directory)) {
            return [];
        }

        foreach (scandir($directory) as $file) {
            if (\in_array($file, ['.', '..'], strict: true)) {
                continue;
            }

            if (! $filter($file, $directory)) {
                continue;
            }

            $path = $directory . '/' . $file;

            if (is_dir($path)) {
                $files = array_merge($files, $this->findVueFiles($path, $baseDirectory, $namespace, $depth - 1, $filter));
            } else {
                if (str($path)->endsWith($this->getExtensions())) {
                    $files[] = [
                        'namespace' => $namespace,
                        'path' => str($path)
                            ->chopStart(base_path())
                            ->replace('\\', '/')
                            ->ltrim('/')
                            ->toString(),
                        'identifier' => $this->identifierGenerator->generate($this, $path, $baseDirectory, $namespace),
                    ];
                }
            }
        }

        return $files;
    }

    protected function evaluateComponentCollection(array $collection): array
    {
        return collect($collection)
            ->flatMap('call_user_func')
            ->reverse() // last registered get priority
            ->unique('path')
            ->unique('identifier')
            ->values()
            ->all();
    }
}
