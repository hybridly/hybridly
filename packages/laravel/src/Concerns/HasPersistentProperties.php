<?php

namespace Hybridly\Concerns;

use Hybridly\SerializesProperties;
use Illuminate\Contracts\Support\Arrayable;

trait HasPersistentProperties
{
    /**
     * The properties marked as persistent.
     */
    private(set) array $persistedProperties = [];

    /**
     * Marks the given properties as persisted, which means they will always be present, even in partial hybrid responses.
     */
    public function persist(string|iterable $properties): static
    {
        if (\is_array($properties)) {
            $this->persistedProperties = array_merge($this->persistedProperties, $properties);
        } elseif ($properties instanceof SerializesProperties) {
            $this->persistedProperties = array_merge($this->persistedProperties, $properties->toHybridArray());
        } elseif ($properties instanceof Arrayable) {
            $this->persistedProperties = array_merge($this->persistedProperties, $properties->toArray());
        } else {
            $this->persistedProperties[] = $properties;
        }

        return $this;
    }
}
