<?php

namespace Hybridly\Tables\Actions\Concerns;

trait CanDeselectAfterExecuted
{
    protected \Closure|bool $deselect = true;

    /**
     * Should deselect all records after action is executed.
     */
    public function deselect(\Closure|bool $deselect = true): static
    {
        $this->deselect = $deselect;

        return $this;
    }

    /**
     * Keep all selected records after action is executed.
     */
    public function keepSelected(): static
    {
        $this->deselect = false;

        return $this;
    }

    public function jsonSerialize(): mixed
    {
        return [
            ...parent::jsonSerialize(),
            'deselect' => $this->shouldDeselect(),
        ];
    }

    protected function shouldDeselect(): bool
    {
        return (bool) $this->evaluate($this->deselect);
    }
}
