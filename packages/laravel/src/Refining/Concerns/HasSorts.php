<?php

namespace Hybridly\Refining\Concerns;

use Hybridly\Refining\Filters;
use Hybridly\Refining\SerializableSort;
use Hybridly\Refining\Sort;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/** @mixin Filters */
trait HasSorts
{
    protected ?Collection $registeredSorts = null;

    public function sort(
        string $name,
        ?string $column = null,
        null|bool|string $default = false,
        ?\Closure $sortQuery = null,
    ): static {
        $this->registeredSorts ??= collect();

        if (class_exists($name) && is_subclass_of($name, Sort::class)) {
            $this->registeredSorts->push($name);

            return $this;
        }

        $this->registeredSorts->push(new Sort(
            name: $name,
            column: $column,
            default: $default,
            sortQuery: $sortQuery,
        ));

        return $this;
    }

    protected function applySortingQuery(Builder $query): Builder
    {
        if (\is_null($currentRequestSorts = $this->getCurrentSorts())) {
            return $query;
        }

        $this->registeredSorts->each(function (Sort $sort) use ($currentRequestSorts, $query) {
            if ($sort->isDefaultSort()) {
                $sort->applySortQuery($query);
            }

            /** @var SerializableSort $sort */
            if (!$sort = data_get($currentRequestSorts, $sort->getName())) {
                return;
            }

            $sort->applySortQuery($query, $sort->direction);
        });

        return $query;
    }

    protected function getCurrentSorts(): Collection
    {
        if (blank($sorts = $this->getRequest()->get($this->formatScope('sorts')))) {
            return collect();
        }

        return collect(explode(',', $sorts))->mapWithKeys(function (string $sort) {
            if (!$this->registeredSorts->contains(ltrim($sort, '-'))) {
                return [];
            }

            $name = ltrim($sort, '-');

            return [
                $name => new SerializableSort(
                    sort: $sort,
                    column: $name,
                ),
            ];
        });
    }
}
