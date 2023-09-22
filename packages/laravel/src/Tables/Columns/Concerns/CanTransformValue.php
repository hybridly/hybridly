<?php

namespace Hybridly\Tables\Columns\Concerns;

use Hybridly\Components\Component;

/** @mixin Component */
trait CanTransformValue
{
    protected null|\Closure $getValueUsing = null;

    /**
     * Transforms the value of the column using the given callback.
     */
    public function transformValueUsing(\Closure $callback): static
    {
        $this->getValueUsing = $callback;

        return $this;
    }

    public function canTransformValue(): bool
    {
        return !\is_null($this->getValueUsing);
    }

    public function getTransformedValue(array $named = [], array $typed = []): mixed
    {
        return $this->evaluate(
            value: $this->getValueUsing,
            named: $named,
            typed: $typed,
        );
    }
}
