<?php

namespace Hybridly\Components\Concerns;

trait HasScope
{
    protected ?string $scope = null;

    protected function getScope(): ?string
    {
        return $this->scope;
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
