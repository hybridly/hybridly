<?php

namespace Hybridly\Refining\Concerns;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Database\Query\Expression;

trait QualifiesColumns
{
    protected \Closure|bool $qualifiesColumn = true;

    public function withoutQualifyingColumn(): static
    {
        $this->qualifiesColumn = false;

        return $this;
    }

    public function qualifyColumn(Builder $builder, string|Expression $column): string
    {
        if (!$this->qualifiesColumn()) {
            return $column;
        }

        return $builder->qualifyColumn($column);
    }

    public function qualifiesColumn(): bool
    {
        return $this->evaluate($this->qualifiesColumn);
    }
}
