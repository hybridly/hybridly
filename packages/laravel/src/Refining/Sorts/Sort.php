<?php

namespace Hybridly\Refining\Sorts;

use Hybridly\Components;
use Hybridly\Refining\Contracts\Refiner as RefinerContract;
use Hybridly\Refining\Contracts\Sort as SortContract;
use Hybridly\Refining\Refine;
use Illuminate\Contracts\Database\Eloquent\Builder;

class Sort extends Components\Component implements RefinerContract
{
    use Components\Concerns\HasLabel;
    use Components\Concerns\HasMetadata;
    use Components\Concerns\HasName;
    use Concerns\HasDefault;

    protected null|string $direction = null;

    public function __construct(
        protected SortContract $sort,
        protected string $property,
        protected ?string $alias = null,
    ) {
        $this->name($alias ?? $property);
        $this->label(str($this->getName())->headline()->lower()->ucfirst());
        $this->setUp();
    }

    public function refine(Refine $refiner, Builder $builder): void
    {
        if (\is_null($this->direction = $refiner->getSortDirectionFromRequest($this->property, $this->alias) ?? $this->getDefaultDirection())) {
            return;
        }

        $this->sort->__invoke($builder, $this->direction, $this->property);
    }

    protected function getDefaultEvaluationParameters(): array
    {
        return [
            'filter' => $this,
            'direction' => $this->direction,
        ];
    }

    public function isActive(): bool
    {
        return !\is_null($this->direction);
    }

    protected function getDescendingValue(): string
    {
        return "-{$this->getName()}";
    }

    protected function getAscendingValue(): string
    {
        return $this->getName();
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'metadata' => $this->getMetadata(),
            'is_active' => $this->isActive(),
            'direction' => $this->direction,
            'default' => $this->getDefaultDirection(),
            'desc' => $this->getDescendingValue(),
            'asc' => $this->getAscendingValue(),
            'next' => match ($this->direction) {
                'desc' => null,
                'asc' => $this->getAscendingValue(),
                default => $this->getDescendingValue(),
            },
        ];
    }
}
