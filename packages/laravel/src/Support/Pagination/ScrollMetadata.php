<?php

namespace Hybridly\Support\Pagination;

use Illuminate\Contracts\Support\Arrayable;

interface ScrollMetadata extends Arrayable
{
    public function type(): string;

    public function queryKey(): string;

    public function current(): ?int;

    public function previous(): ?int;

    public function next(): ?int;

    /**
     * @return array{type: string, queryKey: string, current: ?int, previous: ?int, next: ?int}
     */
    public function toArray();
}
