<?php

namespace Hybridly\Components\Concerns;

trait HasScope
{
    public function scope(null|\Closure|string $scope): static
    {
        $this->scope = $scope;

        return $this;
    }

    protected function getScope(): ?string
    {
        return $this->evaluate($this->scope ?? null);
    }

    protected function formatScope(?string $type = null): ?string
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
