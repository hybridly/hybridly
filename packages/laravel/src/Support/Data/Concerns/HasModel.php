<?php

namespace Hybridly\Support\Data\Concerns;

use Illuminate\Database\Eloquent\Model;

trait HasModel
{
    /**
     * Sets the model used for determining the authorizations for this resource.
     */
    public function usingModel(Model $model): static
    {
        $this->model = $model;

        return $this;
    }
}
