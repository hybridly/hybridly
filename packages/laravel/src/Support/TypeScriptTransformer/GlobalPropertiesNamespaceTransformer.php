<?php

namespace Hybridly\Support\TypeScriptTransformer;

final class GlobalPropertiesNamespaceTransformer
{
    public function __invoke(?string $namespace = null): ?string
    {
        if (!$namespace) {
            return null;
        }

        return str_replace('\\', '.', $namespace);
    }
}
