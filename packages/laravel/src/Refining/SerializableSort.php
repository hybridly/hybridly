<?php

namespace Hybridly\Refining;

use Illuminate\Contracts\Support\Arrayable;

final class SerializableSort implements Arrayable
{
    public readonly string $direction;
    public readonly string $next;

    public function __construct(
        public readonly string $sort,
        public readonly string $column,
    ) {
        $this->direction = '-' === $this->sort[0] ? 'desc' : 'asc';
        $this->next = match (true) {
            $this->sort === $this->column => "-{$this->column}",
            '-' === $this->sort[0] => null,
            default => $this->column
        };
    }

    public function toArray(): array
    {
        return [
            'sort' => $this->sort,
            'column' => $this->column,
            'direction' => $this->direction,
            'next' => $this->next,
        ];
    }
}
