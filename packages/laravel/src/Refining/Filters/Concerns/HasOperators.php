<?php

namespace Hybridly\Refining\Filters\Concerns;

use Closure;
use Hybridly\Refining\Filters\Operator;

trait HasOperators
{
    protected Closure|array $supportedOperators = [];
    protected Closure|Operator|null $defaultOperator = null;

    /**
     * Sets the supported operators for this filter.
     *
     * @param  array<Operator>  $operators
     */
    public function supportedOperators(Closure|array|Operator $operators): static
    {
        $this->supportedOperators = ($operators instanceof Operator) ? [$operators] : $operators;

        return $this;
    }

    /**
     * Sets the default operator for this filter.
     */
    public function defaultOperator(Closure|Operator $operator): static
    {
        $this->defaultOperator = $operator;

        return $this;
    }

    /**
     * Gets the list of supported operators for this filter.
     *
     * @return array<string>
     */
    protected function getSupportedOperators(): array
    {
        return array_map(
            fn (Operator $operator) => $operator->value,
            $this->evaluate($this->supportedOperators),
        );
    }

    /**
     * Gets the default operator for this filter.
     */
    protected function getDefaultOperator(): ?string
    {
        return $this->evaluate($this->defaultOperator)?->value;
    }

    /**
     * Resolves the operator to use.
     */
    protected function resolveOperator(): ?Operator
    {
        $default = $this->evaluate($this->defaultOperator);
        $supported = $this->evaluate($this->supportedOperators);

        if (! in_array($this->filter?->operator, $supported, strict: true)) {
            return $default;
        }

        return $this->filter?->operator ?? $default;
    }
}
