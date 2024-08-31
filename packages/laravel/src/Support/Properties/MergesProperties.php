<?php

namespace Hybridly\Support\Properties;

trait MergesProperties
{
    public function merge(): static
    {
        $this->merge = true;

        return $this;
    }

    public function unique(): static
    {
        $this->unique = true;

        return $this;
    }

    public function shouldMerge(): bool
    {
        return $this->merge;
    }

    public function shouldBeUnique(): bool
    {
        return $this->unique;
    }
}
