<?php

namespace Monolikit\Concerns;

use Illuminate\Contracts\Support\Arrayable;
use Monolikit\Support\Arr;

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
