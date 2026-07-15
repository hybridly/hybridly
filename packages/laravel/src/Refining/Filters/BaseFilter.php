<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Components;
use Hybridly\Refining;
use Hybridly\Refining\Contracts\Filter;
use Hybridly\Refining\Contracts\Refiner;
use Hybridly\Refining\FilterState;
use Hybridly\Refining\Refine;
use Illuminate\Contracts\Database\Eloquent\Builder;
use InvalidArgumentException;

abstract class BaseFilter extends Components\Component implements Refiner, Filter
{
    use Components\Concerns\Configurable;
    use Components\Concerns\HasLabel;
    use Components\Concerns\HasIcon;
    use Components\Concerns\HasMetadata;
    use Components\Concerns\HasName;
    use Components\Concerns\IsHideable;
    use Concerns\HasDefaultValue;
    use Concerns\HasOperators;
    use Concerns\HasType;
    use Concerns\HasPreviewLabel;
    use Refining\Concerns\QualifiesColumns;
    use Refining\Concerns\HasRefineInstance;

    protected ?Refining\Filters\QueryFilter $filter = null;
    protected ?FilterState $effectiveDefault = null;
    protected bool $hasResolvedState = false;
    protected bool $isCleared = false;
    protected bool $isOverridden = false;

    public function __construct(
        protected string $property,
        protected ?string $alias = null,
    ) {
        $this->name(str($alias ?? $property)->replace('.', '_'));
        $this->label(str($this->getName())->headline()->lower()->ucfirst());
        $this->type('filter');
        $this->configure();
        $this->appendMetadata(fn () => array_filter([
            'preview_label' => $this->getPreviewLabel(),
        ]));
    }

    public function refine(Refine $refine, Builder $builder): void
    {
        $this->setRefineInstance($refine);

        $this->filter = $this->resolveFilter($refine);

        if (\is_null($this->filter)) {
            return;
        }

        try {
            $this->apply($builder, $this->filter, $this->property);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function isActive(): bool
    {
        return ! \is_null($this->filter);
    }

    public function normalizeState(FilterState $state): FilterState
    {
        $supported = $this->evaluate($this->supportedOperators);

        if ($state->operator !== null && ! in_array($state->operator, $supported, strict: true)) {
            throw new InvalidArgumentException("Unsupported operator [{$state->operator->value}] for filter [{$this->getName()}].");
        }

        return new FilterState(
            value: $state->value,
            operator: $state->operator ?? $this->evaluate($this->defaultOperator),
            options: $state->options,
            suggestionKey: $state->suggestionKey,
        );
    }

    public function jsonSerialize(): mixed
    {
        $default = $this->getEffectiveDefaultState();

        return [
            'name' => $this->getName(),
            'hidden' => $this->isHidden(),
            'label' => $this->getLabel(),
            'type' => $this->getType(),
            'icon' => $this->getIcon(),
            'metadata' => $this->getMetadata(),
            'is_active' => $this->isActive(),
            'value' => $this->getValue(),
            'search_query' => $this->filter?->search,
            'operator' => $this->resolveOperator()?->value,
            'default_operator' => $default?->operator->value ?? $this->getDefaultOperator(),
            'supported_operators' => $this->getSupportedOperators(),
            'default' => $default === null ? null : $this->serializeStateValue($default->value),
            'has_default' => $default !== null,
            'default_options' => $default->options ?? [],
            'default_suggestion_key' => $default?->suggestionKey,
            'suggestion_key' => $this->filter?->suggestionKey,
            'is_overridden' => $this->isOverridden,
            'is_cleared' => $this->isCleared,
            'options' => $this->filter->options ?? [],
        ];
    }

    protected function resolveFilter(Refine $refine): ?QueryFilter
    {
        $this->effectiveDefault = $this->normalizeFilterStateOperator(
            $this->resolveFilterState(
                $refine->getEffectiveFilterDefault($this->getName(), $this->getDeclaredDefaultState()),
            ),
        );
        $this->hasResolvedState = true;
        $this->isCleared = $refine->isFilterCleared($this->getName());

        $filter = $refine->getQueryFilterFromRequest(
            property: $this->property,
            alias: $this->alias,
            default: $this->effectiveDefault,
        );
        $filter = $filter === null
            ? null
            : $this->normalizeQueryFilterOperator($this->resolveQueryFilter($filter));
        $this->isOverridden = $this->resolveIsOverridden($refine, $filter);

        return $filter;
    }

    protected function getDeclaredDefaultState(): ?FilterState
    {
        if (! $this->hasDefaultValue()) {
            return null;
        }

        return new FilterState(
            value: $this->getDefaultValue(),
            operator: $this->evaluate($this->defaultOperator),
        );
    }

    protected function getEffectiveDefaultState(): ?FilterState
    {
        if ($this->hasResolvedState) {
            return $this->effectiveDefault;
        }

        return $this->resolveFilterState($this->getDeclaredDefaultState());
    }

    protected function resolveFilterState(?FilterState $state): ?FilterState
    {
        return $state;
    }

    protected function resolveQueryFilter(QueryFilter $filter): ?QueryFilter
    {
        return $filter;
    }

    protected function serializeStateValue(mixed $value): mixed
    {
        return $value;
    }

    protected function normalizeOperator(?Operator $operator): ?Operator
    {
        $supported = $this->evaluate($this->supportedOperators);

        if ($operator !== null && in_array($operator, $supported, strict: true)) {
            return $operator;
        }

        return $this->evaluate($this->defaultOperator);
    }

    protected function resolveIsOverridden(Refine $refine, ?QueryFilter $filter): bool
    {
        if (! $refine->hasFilterRequest($this->getName())) {
            return false;
        }

        if ($this->isCleared) {
            return $this->effectiveDefault !== null;
        }

        if ($filter === null || $this->effectiveDefault === null) {
            return $filter !== null || $this->effectiveDefault !== null;
        }

        return (
            $this->serializeStateValue($filter->value) !== $this->serializeStateValue($this->effectiveDefault->value)
            || ($filter->operator ?? $this->evaluate($this->defaultOperator)) !== ($this->effectiveDefault->operator ?? $this->evaluate($this->defaultOperator))
            || $filter->options !== $this->effectiveDefault->options
            || $filter->suggestionKey !== $this->effectiveDefault->suggestionKey
        );
    }

    private function normalizeFilterStateOperator(?FilterState $state): ?FilterState
    {
        if ($state === null) {
            return null;
        }

        return new FilterState(
            value: $state->value,
            operator: $this->normalizeOperator($state->operator),
            options: $state->options,
            suggestionKey: $state->suggestionKey,
        );
    }

    private function normalizeQueryFilterOperator(?QueryFilter $filter): ?QueryFilter
    {
        if ($filter === null) {
            return null;
        }

        return new QueryFilter(
            value: $filter->value,
            search: $filter->search,
            operator: $this->normalizeOperator($filter->operator),
            options: $filter->options,
            suggestionKey: $filter->suggestionKey,
        );
    }

    protected function getQueryBoolean(): string
    {
        return Refine::getGroupOption('boolean', default: 'and');
    }

    protected function getValue(): mixed
    {
        return $this->filter?->value;
    }

    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): array
    {
        return match ($parameterType) { default => [] };
    }

    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'filter' => [$this->filter],
            'value' => [$this->filter?->value],
            'search' => [$this->filter?->search],
            'property' => [$this->property],
            'alias' => [$this->alias],
            'parentBuilder' => [$this->parentBuilder],
            default => [],
        };
    }
}
