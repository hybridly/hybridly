<?php

namespace Hybridly\Concerns;

use Illuminate\Contracts\Support\Arrayable;

trait HasPersistentProperties
{
    protected array $persistentProperties = [];

    /**
     * Marks the given properties as persisted, which means they will
     * always be present, even in partial Hybridly responses.
     */
    public function persist(string|array|Arrayable $properties): static
    {
        if (\is_array($properties)) {
            $this->persistentProperties = array_merge($this->persistentProperties, $properties);
        } elseif ($properties instanceof Arrayable) {
            $this->persistentProperties = array_merge($this->persistentProperties, $properties->toArray());
        } else {
            array_push($this->persistentProperties, $properties);
        }

        return $this;
    }

    /**
     * Gets data being persisted.
     */
    public function persisted(): array
    {
        return $this->persistentProperties;
    }
}
