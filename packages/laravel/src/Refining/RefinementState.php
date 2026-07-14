<?php

declare(strict_types=1);

namespace Hybridly\Refining;

use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

/** Contains a complete, serializable filter and sort refinement baseline. */
final readonly class RefinementState implements Arrayable, JsonSerializable
{
    /**
     * @param  array<string, FilterState>  $filters
     * @param  array<SortState>  $sorts
     */
    public function __construct(
        public array $filters = [],
        public array $sorts = [],
    ) {}

    public static function fromArray(array $state): self
    {
        return new self(
            filters: collect(data_get(target: $state, key: 'filters', default: []))
                ->map(FilterState::fromArray(...))
                ->all(),
            sorts: array_map(
                SortState::fromArray(...),
                data_get(target: $state, key: 'sorts', default: []),
            ),
        );
    }

    public function filter(string $name): ?FilterState
    {
        return $this->filters[$name] ?? null;
    }

    public function sort(string $name): ?SortState
    {
        return array_find(
            $this->sorts,
            static fn (SortState $sort): bool => $sort->name === $name,
        );
    }

    public function toArray(): array
    {
        return [
            'filters' => array_map(
                static fn (FilterState $filter): array => $filter->toArray(),
                $this->filters,
            ),
            'sorts' => array_map(
                static fn (SortState $sort): array => $sort->toArray(),
                $this->sorts,
            ),
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
