<?php

namespace Hybridly\Refining\Concerns;

use Hybridly\Refining\Filters;
use Illuminate\Database\Eloquent\Builder;

/** @mixin Filters */
trait HasBuilderInstance
{
    protected ?Builder $builderInstance = null;

    public function setBuilderInstance(Builder $builderInstance): static
    {
        $this->builderInstance = $builderInstance;

        return $this;
    }

    public function getBuilderInstance(): Builder
    {
        if (!$this->builderInstance) {
            throw new \RuntimeException('No builder instance has been defined.');
        }

        return $this->builderInstance;
    }
}
