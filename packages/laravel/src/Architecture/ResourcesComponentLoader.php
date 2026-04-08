<?php

namespace Hybridly\Architecture;

use Illuminate\Support\Str;

final readonly class ResourcesComponentLoader implements ComponentLoader
{
    public function load(): array
    {
        return $this->findVueFiles(
            directory: resource_path(),
            baseDirectory: resource_path(),
            depth: 10,
        );
    }

    /**
     * @return array<{path: string, identifier: string}>
     */
    protected function findVueFiles(string $directory, string $baseDirectory, int $depth): array
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
                $files = [...$files, ...$this->findVueFiles($path, $baseDirectory, $depth - 1)];
            } else {
                $type = match (true) {
                    Str::endsWith($path, ['.view.vue', '.view.tsx']) => ComponentType::VIEW,
                    Str::endsWith($path, ['.layout.vue', '.layout.tsx']) => ComponentType::LAYOUT,
                    default => null,
                };

                if ($type instanceof ComponentType) {
                    $files[] = new Component(
                        type: $type,
                        path: $this->normalizePath($path),
                        identifier: $this->generateIdentifier($baseDirectory, $path),
                    );
                }
            }
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

    private function generateIdentifier(string $directory, string $path): string
    {
        return str($path)
            ->after($directory)
            ->ltrim('/\\')
            ->replace(['/', '\\'], '.')
            ->chopEnd(['.view.vue', '.layout.vue', '.view.tsx', '.layout.tsx'])
            ->explode('.')
            ->map(fn (string $str) => str($str)->kebab())
            ->join('.');
    }
}
