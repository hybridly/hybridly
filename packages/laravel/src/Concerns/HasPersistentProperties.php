<?php

namespace Hybridly\Concerns;

use Illuminate\Contracts\Support\Arrayable;

trait HasPersistentProperties
{
    protected array $persistent = [];

    /**
     * Marks the given properties as persisted, which means they will
     * always be present, even in partial Hybridly responses.
     */
    public function persist(string|array|Arrayable $properties): static
    {
        if (\is_array($properties)) {
            $this->persistent = array_merge($this->persistent, $properties);
        } elseif ($properties instanceof Arrayable) {
            $this->persistent = array_merge($this->persistent, $properties->toArray());
        } else {
            array_push($this->persistent, $properties);
        }

        return $this;
    }

    /**
     * Gets data being persisted.
     */
    public function persisted(): array
    {
        return $this->persistent;
    }
}
