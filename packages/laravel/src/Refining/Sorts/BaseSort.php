<?php

namespace Hybridly\Refining\Sorts;

use Hybridly\Components;
use Hybridly\Refining;
use Hybridly\Refining\Contracts\Refiner;
use Hybridly\Refining\Contracts\Sort;
use Hybridly\Refining\Refine;
use Hybridly\Refining\SortState;
use Illuminate\Contracts\Database\Eloquent\Builder;

abstract class BaseSort extends Components\Component implements Refiner, Sort
{
    use Components\Concerns\Configurable;
    use Components\Concerns\HasLabel;
    use Components\Concerns\HasMetadata;
    use Components\Concerns\HasName;
    use Components\Concerns\IsHideable;
    use Concerns\HasDefault;
    use Refining\Concerns\QualifiesColumns;
    use Refining\Concerns\HasRefineInstance;

    protected ?string $requestedDirection = null;
    protected ?string $currentDirection = null;
    protected ?SortState $effectiveDefault = null;
    protected bool $isCleared = false;
    protected bool $isOverridden = false;
    protected \Closure|bool $isDirectionCycleInverted = false;

    public function __construct(
        protected string $property,
        protected ?string $alias = null,
    ) {
        $this->name($alias ?? $property);
        $this->label(str($this->getName())->headline()->lower()->ucfirst());
        $this->configure();
    }

    public function getProperty(): string
    {
        return $this->property;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function refine(Refine $refine, Builder $builder): void
    {
        $this->setRefineInstance($refine);

        $this->requestedDirection = $refine->getSortDirectionFromRequest($this);
        $this->effectiveDefault = $refine->getEffectiveSortDefault($this);
        $this->isCleared = $refine->areSortsCleared() && $this->effectiveDefault !== null;
        $this->isOverridden = $this->resolveIsOverridden($refine);

        if ($refine->areSortsCleared()) {
            $this->currentDirection = null;

            return;
        }

        if ($refine->hasReplacementBaseline()) {
            $this->currentDirection = $this->resolveCurrentDirection($refine);

            if ($this->currentDirection === null) {
                return;
            }

            $this->apply($builder, $this->currentDirection, $this->property);

            return;
        }

        if ($this->requestedDirection === null && $this->isSole() && $refine->hasOtherSorts($this)) {
            $this->currentDirection = null;

            return;
        }

        if (\is_null($this->requestedDirection) && ! $this->getDefaultDirection()) {
            $this->currentDirection = null;

            return;
        }

        $this->currentDirection = $this->requestedDirection ?? $this->getDefaultDirection();
        $this->apply($builder, $this->currentDirection, $this->property);
    }

    public function setRefineInstance(Refine $refine): void
    {
        $this->refine = $refine;
    }

    public function isActive(): bool
    {
        return $this->currentDirection !== null;
    }

    public function normalizeState(SortState $state): SortState
    {
        return $state;
    }

    public function jsonSerialize(): mixed
    {
        $hasRefineInstance = isset($this->refine);
        $default = $this->getSerializedDefault();

        return [
            'name' => $this->getName(),
            'hidden' => $this->isHidden(),
            'label' => $this->getLabel(),
            'metadata' => $this->getMetadata(),
            'is_active' => $this->isActive(),
            'direction' => $this->currentDirection,
            'default' => $default?->direction,
            'has_default' => $default !== null,
            'current_order' => $hasRefineInstance ? $this->refine->getEffectiveCurrentSortOrder($this) : null,
            'default_order' => $hasRefineInstance ? $this->refine->getEffectiveSortDefaultOrder($this) : null,
            'is_overridden' => $this->isOverridden,
            'is_cleared' => $this->isCleared,
            'desc' => $this->getDescendingValue(),
            'asc' => $this->getAscendingValue(),
            'next' => $this->getNextDirection(),
        ];
    }

    /**
     * Inverts the direction cycle. By default, the order is `asc`, `desc`, then default.
     */
    public function invertDirectionCycle(bool|\Closure $invert = true): self
    {
        $this->isDirectionCycleInverted = $invert;

        return $this;
    }

    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): array
    {
        return match ($parameterType) {
            Refiner::class => [$this->sort],
            Sort::class => [$this->sort],
            default => [],
        };
    }

    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'sort' => [$this->sort],
            'direction' => [$this->currentDirection],
            'property' => [$this->property],
            'alias' => [$this->alias],
            default => [],
        };
    }

    protected function getNextDirection(): ?string
    {
        if ($this->isDirectionCycleInverted()) {
            return match ($this->currentDirection) {
                'desc' => $this->getAscendingValue(),
                'asc' => null,
                default => $this->getDescendingValue(),
            };
        }

        return match ($this->currentDirection) {
            'desc' => null,
            'asc' => $this->getDescendingValue(),
            default => $this->getAscendingValue(),
        };
    }

    protected function resolveCurrentDirection(Refine $refine): ?string
    {
        if ($refine->areSortsCleared()) {
            return null;
        }

        if ($refine->hasRequestedSorts()) {
            return $this->requestedDirection;
        }

        return $this->effectiveDefault?->direction;
    }

    protected function resolveIsOverridden(Refine $refine): bool
    {
        if (! $refine->hasRequestedSorts() && ! $refine->areSortsCleared()) {
            return false;
        }

        $current = array_find(
            $refine->getEffectiveCurrentSorts(),
            fn (SortState $sort): bool => $sort->name === $this->getName(),
        );

        return $current?->direction !== $this->effectiveDefault?->direction || $refine->getEffectiveCurrentSortOrder($this) !== $refine->getEffectiveSortDefaultOrder($this);
    }

    protected function isDirectionCycleInverted(): bool
    {
        return $this->evaluate($this->isDirectionCycleInverted);
    }

    protected function getDescendingValue(): string
    {
        return "-{$this->getName()}";
    }

    protected function getAscendingValue(): string
    {
        return $this->getName();
    }

    private function getSerializedDefault(): ?SortState
    {
        if (isset($this->refine)) {
            return $this->effectiveDefault;
        }

        if (! $this->hasDefaultDirection()) {
            return null;
        }

        $direction = $this->getDefaultDirection();

        if ($direction === null) {
            return null;
        }

        return new SortState(
            name: $this->getName(),
            direction: $direction,
        );
    }
}
