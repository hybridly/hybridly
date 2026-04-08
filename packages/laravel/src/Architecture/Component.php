<?php

namespace Hybridly\Architecture;

use JsonSerializable;

/**
 * Represents a front-end component, view or layout.
 */
final readonly class Component implements JsonSerializable
{
    /**
     * @param string $path The absolute path to the file.
     * @param string $identifier The identifier to associate the component to.
     */
    public function __construct(
        public ComponentType $type,
        public string $path,
        public string $identifier,
    ) {}

    public function jsonSerialize(): mixed
    {
        return [
            'path' => $this->path,
            'identifier' => $this->identifier,
        ];
    }
}
