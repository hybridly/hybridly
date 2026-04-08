<?php

namespace Hybridly\Architecture;

use Illuminate\Support\Str;

final class ModulesComponentLoader implements ComponentLoader
{
    /** @return Component[] */
    public function load(): array
    {
        $components = [];

        foreach ($this->namespaceDirectories() as $directory) {
            $components = [
                ...$components,
                ...$this->findVueFiles(
                    directory: $directory,
                    namespaceRoot: $directory,
                    depth: 10,
                ),
            ];
        }

        return $components;
    }

    /** @return Component[] */
    protected function findVueFiles(string $directory, string $namespaceRoot, int $depth): array
    {
        if ($depth === 0) {
            return [];
        }

        $files = [];

        if (! is_dir($directory)) {
            return [];
        }

        foreach (scandir($directory) as $file) {
            if (\in_array($file, ['.', '..'], strict: true)) {
                continue;
            }

            $path = $directory . '/' . $file;

            if (is_dir($path)) {
                $files = [...$files, ...$this->findVueFiles($path, $namespaceRoot, $depth - 1)];
                continue;
            }

            $type = match (true) {
                Str::endsWith($path, ['.view.vue', '.view.tsx']) => ComponentType::VIEW,
                Str::endsWith($path, ['.layout.vue', '.layout.tsx']) => ComponentType::LAYOUT,
                default => null,
            };

            if (! ($type instanceof ComponentType)) {
                continue;
            }

            $files[] = new Component(
                type: $type,
                path: $this->normalizePath($path),
                identifier: $this->generateIdentifier($namespaceRoot, $path),
            );
        }

        return $files;
    }

    private function normalizePath(string $path): string
    {
        return str($path)
            ->chopStart(base_path())
            ->replace('\\', '/')
            ->ltrim('/')
            ->toString();
    }

    private function generateIdentifier(string $namespaceRoot, string $path): string
    {
        $relativePath = str($path)
            ->after($namespaceRoot)
            ->ltrim('/\\')
            ->replace('\\', '/')
            ->toString();

        $segments = array_values(array_filter(explode('/', $relativePath)));
        $namespace = count($segments) === 1
            ? null
            : str(array_shift($segments))->kebab()->toString();

        $identifier = str(implode('/', $segments))
            ->replace(['/', '\\'], '.')
            ->chopEnd(['.view.vue', '.layout.vue', '.view.tsx', '.layout.tsx'])
            ->explode('.')
            ->filter(static fn (string $segment) => $segment !== '')
            ->map(static fn (string $segment) => str($segment)->kebab()->toString())
            ->join('.');

        if ($namespace) {
            return "{$namespace}::{$identifier}";
        }

        return $identifier;
    }

    /** @return string[] */
    private function namespaceDirectories(): array
    {
        $composer = json_decode(file_get_contents(base_path('composer.json')), associative: true);

        if (! is_array($composer['autoload']['psr-4'] ?? null)) {
            return [];
        }

        $directories = [];

        foreach ($composer['autoload']['psr-4'] as $path) {
            foreach ((array) $path as $candidate) {
                if (! is_string($candidate)) {
                    continue;
                }

                $directories[] = str(base_path($candidate))
                    ->replace('\\', '/')
                    ->rtrim('/')
                    ->toString();
            }
        }

        return array_unique($directories) |> array_values(...);
    }
}
