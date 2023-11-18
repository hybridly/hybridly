<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Components\Component;
use Hybridly\Components\Concerns\HasLabel;
use Hybridly\Components\Concerns\HasMetadata;
use Hybridly\Components\Concerns\HasName;
use Hybridly\Components\Concerns\IsHideable;
use Hybridly\Components\Contracts\Hideable;
use Hybridly\Refining\Contracts\Filter;
use Hybridly\Refining\Contracts\Refiner;
use Hybridly\Refining\Filters\Concerns\HasDefaultValue;
use Hybridly\Refining\Filters\Concerns\HasType;
use Hybridly\Refining\Refine;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

abstract class BaseFilter extends Component implements Hideable, Refiner, Filter
{
    use HasLabel;
    use HasMetadata;
    use HasName;
    use IsHideable;
    use HasDefaultValue;
    use HasType;

    protected mixed $value = null;

    public function __construct(
        protected string $property,
        protected ?string $alias = null,
    ) {
        $this->name(str($alias ?? $property)->replace('.', '_'));
        $this->label(str($this->getName())->headline()->lower()->ucfirst());
        $this->type('filter');
        $this->configure();
    }

    public function refine(Refine $refiner, Builder $builder): void
    {
        if (\is_null($this->value = $refiner->getFilterValueFromRequest($this->property, $this->alias) ?? $this->getDefaultValue())) {
            return;
        }

        try {
            $this->apply($builder, $this->value, $this->property);
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
        return !\is_null($this->value);
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->getName(),
            'hidden' => $this->isHidden(),
            'label' => $this->getLabel(),
            'type' => $this->getType(),
            'metadata' => $this->getMetadata(),
            'is_active' => $this->isActive(),
            'value' => $this->value,
            'default' => $this->defaultValue,
        ];
    }

    protected function getQueryBoolean(): string
    {
        return Refine::getGroupOption('boolean', default: 'and');
    }

    protected function resolveDefaultClosureDependencyForEvaluationByType(string $parameterType): array
    {
        return match ($parameterType) {
            Refiner::class => [$this->filter],
            Filter::class => [$this->filter],
            default => []
        };
    }

    protected function resolveDefaultClosureDependencyForEvaluationByName(string $parameterName): array
    {
        return match ($parameterName) {
            'filter' => [$this->filter],
            'value' => [$this->value],
            'property' => [$this->property],
            'alias' => [$this->alias],
            default => []
        };
    }
}
