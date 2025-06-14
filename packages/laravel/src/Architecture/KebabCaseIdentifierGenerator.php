<?php

namespace Hybridly\Architecture;

use Illuminate\Support\Stringable;

final class KebabCaseIdentifierGenerator implements IdentifierGenerator
{
    public function generate(ComponentsResolver $components, string $path, string $baseDirectory, string $namespace): string
    {
        $identifier = str($path)
            ->after($baseDirectory)
            ->ltrim('/\\')
            ->replace(['/', '\\'], '.')
            ->chopEnd(collect($components->getExtensions())->flatMap(fn (string $extension) => [
                ".view{$extension}",
                ".layout{$extension}",
                $extension,
            ])->toArray())
            ->explode('.')
            ->map(fn (string $str) => str($str)->kebab())
            ->join('.');

        return str($identifier)
            ->when($namespace !== 'default')
            ->prepend("{$namespace}::")
            ->toString();
    }
}
