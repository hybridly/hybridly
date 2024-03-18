<?php

namespace Hybridly\Architecture;

final class KebabCaseIdentifierGenerator implements IdentifierGenerator
{
    public function generate(ComponentsResolver $components, string $path, string $baseDirectory, string $namespace): string
    {
        $identifier = str($path)
            ->after($baseDirectory)
            ->ltrim('/\\')
            ->replace(['/', '\\'], '.')
            ->replace($components->getExtensions(), '')
            ->explode('.')
            ->map(fn (string $str) => str($str)->kebab())
            ->join('.');

        return str($identifier)
            ->when($namespace !== 'default')
            ->prepend("{$namespace}::")
            ->toString();
    }
}
