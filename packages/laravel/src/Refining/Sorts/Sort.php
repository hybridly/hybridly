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
            'descending' => $this->direction === 'desc',
        ];
    }

    public function jsonSerialize(): mixed
    {
        return [
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'metadata' => $this->getMetadata(),
            'direction' => $this->direction,
            'descending' => $this->direction === 'desc',
            'next' => $this->direction === 'desc'
                ? $this->getName()
                : "-{$this->getName()}",
        ];
    }
}
