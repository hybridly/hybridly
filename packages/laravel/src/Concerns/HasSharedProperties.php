<?php

namespace Hybridly\Concerns;

use Hybridly\SerializesProperties;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;

trait HasSharedProperties
{
    /**
     * Properties being shared to every response.
     */
    private(set) array $sharedProperties = [];

    /**
     * Shares data to every response.
     */
    public function share(string|array|Arrayable $key, mixed $value = null): static
    {
        if (\is_array($key)) {
            $this->sharedProperties = array_merge($this->sharedProperties, $key);
        } elseif ($key instanceof SerializesProperties) {
            $this->sharedProperties = array_merge($this->sharedProperties, $key->toHybridArray());
        } elseif ($key instanceof Arrayable) {
            $this->sharedProperties = array_merge($this->sharedProperties, $key->toArray());
        } else {
            Arr::set($this->sharedProperties, $key, value($value));
        }

        return $this;
    }

    /**
     * Flushes the shared properties.
     */
    public function flush(): void
    {
        $this->sharedProperties = [];
    }
}
