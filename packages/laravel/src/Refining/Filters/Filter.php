<?php

namespace Hybridly\Refining\Filters;

use Hybridly\Components;
use Hybridly\Refining\Concerns;
use Hybridly\Refining\Contracts\Filter as FilterContract;
use Hybridly\Refining\Contracts\Refiner as RefinerContract;
use Hybridly\Refining\Refine;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

class Filter extends Components\Component implements RefinerContract
{
    use Components\Concerns\HasLabel;
    use Components\Concerns\HasMetadata;
    use Components\Concerns\HasName;
    use Concerns\HasType;

    protected mixed $value = null;

    public function __construct(
        protected FilterContract $filter,
        protected string $property,
        protected array $aliases = [],
    ) {
        $this->name($property);
        $this->label(str($property)->headline()->lower()->ucfirst());
        $this->type($filter->getType());
        $this->setUp();
    }

    public function refine(Refine $refiner, Builder $builder): void
    {
        if (!$this->value = $refiner->getFilterValueFromRequest($this->property, $this->aliases)) {
            return;
        }

        try {
            $this->filter->__invoke($builder, $this->value, $this->property);
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

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'type' => $this->getType(),
            'metadata' => $this->getMetadata(),
        ];
    }
}
