<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Components;
use Hybridly\Refining;
use Hybridly\Refining\Contracts\Filter;
use Hybridly\Refining\Contracts\Refiner;
use Hybridly\Refining\Refine;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

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
    protected Refine $refine;

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

        $this->filter = $refine->getQueryFilterFromRequest(
            property: $this->property,
            alias: $this->alias,
            default: $this->getDefaultValue(),
        );

        if (\is_null($this->filter)) {
            return;
        }

        try {
            $this->apply($builder, $this->filter, $this->property);
        } catch (\TypeError $th) {
            if (str_contains($th->getMessage(), 'Argument #2 ($')) {
                throw ValidationException::withMessages([
                    $this->property => 'This filter is invalid.',
                ]);
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function isActive(): bool
    {
        return ! \is_null($this->filter);
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->getName(),
            'hidden' => $this->isHidden(),
            'label' => $this->getLabel(),
            'type' => $this->getType(),
            'icon' => $this->getIcon(),
            'metadata' => $this->getMetadata(),
            'is_active' => $this->isActive(),
            'value' => $this->filter?->value,
            'search_query' => $this->filter?->search,
            'operator' => $this->resolveOperator(),
            'default_operator' => $this->getDefaultOperator(),
            'supported_operators' => $this->getSupportedOperators(),
            'default' => $this->defaultValue,
            'options' => $this->filter?->options ?? [],
        ];
    }

    protected function getQueryBoolean(): string
    {
        return Refine::getGroupOption('boolean', default: 'and');
    }

    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): array
    {
        return match ($parameterType) {
            default => [],
        };
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
