<?php

namespace Hybridly\Tables\Columns\Concerns;

trait HasExtra
{
    protected array|\Closure $extra = [];

    /**
     * Adds extra data to a cell.
     */
    public function extra(array|\Closure $extra): static
    {
        $this->extra = $extra;

        return $this;
    }

    public function getExtra(array $named = [], array $typed = []): array
    {
        return $this->evaluate($this->extra, $named, $typed);
    }
}
