<?php

namespace Hybridly\Support\Data\Concerns;

trait CanAppendAuthorizations
{
    protected \Closure|bool $appendAuthorizations = true;

    /**
     * Sets whether the data object should be transformed with an array of its defined authorizations.
     */
    public function withAuthorizations(bool $appendAuthorizations = true): static
    {
        $this->appendAuthorizations = $appendAuthorizations;

        return $this;
    }

    /**
     * Defines that authorizations should not be appended to the transformed data resource.
     */
    public function withoutAuthorizations(): static
    {
        $this->appendAuthorizations = false;

        return $this;
    }

    public function appendsAuthorizations(): bool
    {
        if ($this->appendAuthorizations instanceof \Closure) {
            return app()->call($this->appendAuthorizations, [
                'model' => $this->model,
            ]);
        }

        return $this->appendAuthorizations;
    }
}
