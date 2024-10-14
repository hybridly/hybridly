<?php

namespace Hybridly\Components\Concerns;

trait HasScope
{
    private null|\Closure|string $scope;

    public function scope(null|\Closure|string $scope): static
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
        if (!$this->getScope()) {
            return $type;
        }

        return str($this->getScope())
            ->slug()
            ->when($type)->append('-' . $type)
            ->toString();
    }
}
