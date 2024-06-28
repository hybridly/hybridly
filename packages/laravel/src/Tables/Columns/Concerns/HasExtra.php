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

    public function hasExtra(): bool
    {
        if ($this->extra instanceof \Closure) {
            return true;
        }

        return \count($this->extra) > 0;
    }

    public function getExtra(array $named = [], array $typed = []): array
    {
        return $this->evaluate($this->extra, $named, $typed);
    }
}
