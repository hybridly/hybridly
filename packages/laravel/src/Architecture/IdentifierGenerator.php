<?php

namespace Hybridly\Architecture;

interface IdentifierGenerator
{
    /**
     * Generates a file identifier.
     *
     * @param string $path The full path to the file.
     * @param string $baseDirectory The base directory of the file.
     * @param string $namespace The namespace to group the file into.
     */
    public function generate(ComponentsResolver $components, string $path, string $baseDirectory, string $namespace): string;
}
