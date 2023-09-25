<?php

namespace Hybridly\Refining\Sorts;

use Hybridly\Components;
use Hybridly\Refining\Contracts\Refiner;
use Hybridly\Refining\Contracts\Sort;
use Hybridly\Refining\Refine;
use Illuminate\Contracts\Database\Eloquent\Builder;

abstract class BaseSort extends Components\Component implements Refiner, Sort
{
    use Components\Concerns\Configurable;
    use Components\Concerns\HasLabel;
    use Components\Concerns\HasMetadata;
    use Components\Concerns\HasName;
    use Components\Concerns\IsHideable;
    use Concerns\HasDefault;

    protected null|string $direction = null;

    public function __construct(
        protected string $property,
        protected ?string $alias = null,
    ) {
        $this->name($alias ?? $property);
        $this->label(str($this->getName())->headline()->lower()->ucfirst());
        $this->setUp();
    }

    public function refine(Refine $refiner, Builder $builder): void
    {
        $this->direction = $refiner->getSortDirectionFromRequest($this->property, $this->alias);

        if (\is_null($this->direction) && !$this->getDefaultDirection()) {
            return;
        }

        $this->apply($builder, $this->direction ?? $this->getDefaultDirection(), $this->property);
    }

    public function isActive(): bool
    {
        return !\is_null($this->direction);
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->getName(),
            'hidden' => $this->isHidden(),
            'label' => $this->getLabel(),
            'metadata' => $this->getMetadata(),
            'is_active' => $this->isActive(),
            'direction' => $this->direction,
            'default' => $this->getDefaultDirection(),
            'desc' => $this->getDescendingValue(),
            'asc' => $this->getAscendingValue(),
            'next' => match ($this->direction) {
                'desc' => null,
                'asc' => $this->getDescendingValue(),
                default => $this->getAscendingValue(),
            },
        ];
    }

    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): array
    {
        return match ($parameterType) {
            Refiner::class => [$this->sort],
            Sort::class => [$this->sort],
            default => []
        };
    }

    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'sort' => [$this->sort],
            'direction' => [$this->direction],
            'property' => [$this->property],
            'alias' => [$this->alias],
            default => []
        };
    }

    protected function getDescendingValue(): string
    {
        return "-{$this->getName()}";
    }

    protected function getAscendingValue(): string
    {
        return $this->getName();
    }
}
