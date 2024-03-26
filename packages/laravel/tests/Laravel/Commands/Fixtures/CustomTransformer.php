<?php

namespace Hybridly\Tests\Laravel\Commands\Fixtures;

final class CustomTransformer
{
    public function __invoke(?string $namespace): ?string
    {
        if (!$namespace) {
            return null;
        }

        return str_replace('App.', '', str_replace('\\', '.', $namespace));
    }
}
