<?php

namespace Hybridly\Support\Properties;

interface Mergeable
{
    public function merge(): static;

    public function shouldMerge(): bool;

    public function shouldBeUnique(): bool;
}
