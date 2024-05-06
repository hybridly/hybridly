<?php

namespace Hybridly\Resources\Concerns;

use Exception;

trait HasResources
{
    /** @var array<string, callable> */
    protected array $registeredResources = [];

    public function registerResource(string $key, callable $callback): void
    {
        $this->registeredResources[$key] = $callback;
    }

    /**
     * @throws Exception
     */
    public function getResource(string $key): mixed
    {
        if (!$this->hasResource($key)) {
            throw new Exception('Resource does not exist');
        }

        return $this->registeredResources[$key]();
    }

    public function hasResource(string $key): bool
    {
        return isset($this->registeredResources[$key]);
    }
}
