<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Components;
use Hybridly\Refining\Contracts\Filter as FilterContract;
use Hybridly\Refining\Contracts\Refiner as RefinerContract;
use Hybridly\Refining\Refine;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class Filter extends Components\Component implements RefinerContract
{
    use Components\Concerns\HasDefaultValue;
    use Components\Concerns\HasLabel;
    use Components\Concerns\HasMetadata;
    use Components\Concerns\HasName;
    use Concerns\HasType;

    protected mixed $value = null;

    public function __construct(
        protected FilterContract $filter,
        protected string $property,
        protected ?string $alias = null,
    ) {
        $this->name($alias ?? $property);
        $this->label(str($this->getName())->headline()->lower()->ucfirst());
        $this->type($filter->getType());
        $this->setUp();
    }

    public function refine(Refine $refiner, Builder $builder): void
    {
        if (!$this->value = $refiner->getFilterValueFromRequest($this->property, $this->alias) ?? $this->getDefaultValue()) {
            return;
        }

        try {
            $this->evaluate($this->filter, [
                'builder' => $builder,
                'value' => $this->value,
                'property' => $this->property,
            ]);
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

    protected function getDefaultEvaluationParameters(): array
    {
        return [
            'filter' => $this,
            'value' => $this->value,
        ];
    }

    public function isActive(): bool
    {
        return !\is_null($this->value);
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'type' => $this->getType(),
            'metadata' => $this->getMetadata(),
            'is_active' => $this->isActive(),
            'value' => $this->value,
        ];
    }
}
