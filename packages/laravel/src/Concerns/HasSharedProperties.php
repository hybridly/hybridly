<?php

namespace Hybridly\Concerns;

use Hybridly\Support\Arr;
use Illuminate\Contracts\Support\Arrayable;

trait HasSharedProperties
{
    protected array $sharedProperties = [];

    /**
     * Shares data to every response.
     */
    public function share(string|array|Arrayable $key, mixed $value = null): static
    {
        if (\is_array($key)) {
            $this->sharedProperties = array_merge($this->sharedProperties, $key);
        } elseif ($key instanceof Arrayable) {
            $this->sharedProperties = array_merge($this->sharedProperties, $key->toArray());
        } else {
            Arr::set($this->sharedProperties, $key, value($value));
        }

        return $this;
    }

    /**
     * Only apply the chain calls if the condition is true.
     */
    public function when(bool|\Closure $condition): static
    {
        if ($condition instanceof \Closure ? $condition($this) : $condition) {
            return $this;
        }

        return new static();
    }

    /**
     * Unless the condition is true, apply the chain calls.
     */
    public function unless(bool|\Closure $condition): static
    {
        if ($condition instanceof \Closure ? $condition($this) : $condition) {
            return new static();
        }

        return $this;
    }

    /**
     * Gets data being shared to every response.
     */
    public function shared(string $key = null, mixed $default = null): mixed
    {
        if ($key) {
            return data_get($this->sharedProperties, $key, value($default));
        }

        return $this->sharedProperties;
    }

    /**
     * Flushes the shared properties.
     */
    public function flush(): void
    {
        $this->sharedProperties = [];
    }
}
