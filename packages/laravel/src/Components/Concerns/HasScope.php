<?php

namespace Hybridly\Components\Concerns;

trait HasScope
{
    protected \Closure|string|null $scope;

    public function scope(\Closure|string|null $scope): static
    {
        $this->scope = $scope;

        return $this;
    }

    public function getScope(): ?string
    {
        return $this->evaluate($this->scope ?? null);
    }

    public function formatScope(?string $type = null): ?string
    {
        if (! $this->getScope()) {
            return $type;
        }

        return str($this->getScope())
            ->slug()
            ->when($type)
            ->append('-' . $type)
            ->toString();
    }
}
