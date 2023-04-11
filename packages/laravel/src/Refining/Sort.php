<?php

namespace Hybridly\Refining;

use Illuminate\Contracts\Database\Eloquent\Builder;

class Sort
{
    public function __construct(
        protected string $name,
        protected ?string $column = null,
        protected null|bool|string $default = false,
        protected ?\Closure $sortQuery = null,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getColumnName(): string
    {
        return $this->column ?? $this->name;
    }

    public function hasSortQuery(): bool
    {
        return !\is_null($this->sortQuery);
    }

    public function applySortQuery(Builder $query, string $direction = null): void
    {
        $direction ??= $this->getDefaultSortDirection();

        if ($this->sortQuery) {
            $this->evaluate($this->sortQuery, [
                'query' => $query,
                'direction' => $direction,
            ]);

            return;
        }

        $query->orderBy($this->getColumnName(), $direction);
    }

    public function isDefaultSort(): bool
    {
        return $this->default !== false;
    }

    public function getDefaultSortDirection(): string
    {
        return match ($this->default) {
            'desc' => 'desc',
            default => 'asc',
        };
    }
}
