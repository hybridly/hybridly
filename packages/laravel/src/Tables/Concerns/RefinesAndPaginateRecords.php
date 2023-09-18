<?php

namespace Hybridly\Tables\Concerns;

use Hybridly\Refining\Contracts\Refiner;
use Hybridly\Refining\Refine;
use Hybridly\Tables\Columns\BaseColumn;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Contracts\DataCollectable;
use Spatie\LaravelData\Data;

trait RefinesAndPaginateRecords
{
    private null|Refine $refine = null;
    private mixed $cachedRecords = null;
    private mixed $cachedRefiners = null;

    protected function defineRefinements(): array
    {
        return [];
    }

    protected function getPaginatedRecords(): array
    {
        return $this->cachedRecords ??= $this->transformPaginatedRecords()->toArray();
    }

    protected function getRecords(): array
    {
        return data_get($this->getPaginatedRecords(), 'data', []);
    }

    protected function getPaginatorMeta(): array
    {
        $pagination = $this->getPaginatedRecords();

        // Wraps pagination data if necessary
        if (!\array_key_exists('meta', $pagination)) {
            return [
                'links' => $pagination['links'],
                'meta' => [
                    'current_page' => $pagination['current_page'],
                    'first_page_url' => $pagination['first_page_url'],
                    'from' => $pagination['from'],
                    'last_page' => $pagination['last_page'],
                    'last_page_url' => $pagination['last_page_url'],
                    'next_page_url' => $pagination['next_page_url'],
                    'path' => $pagination['path'],
                    'per_page' => $pagination['per_page'],
                    'prev_page_url' => $pagination['prev_page_url'],
                    'to' => $pagination['to'],
                    'total' => $pagination['total'],
                ],
            ];
        }

        return [
            'links' => $pagination['links'],
            'meta' => $pagination['meta'],
        ];
    }

    public function getRefiners(): Collection
    {
        return $this->cachedRefinements ??= collect($this->defineRefinements())
            ->filter(static fn (Refiner $refiner): bool => !$refiner->isHidden());
    }

    protected function transformPaginatedRecords(): Paginator|DataCollectable
    {
        $paginatedRecords = $this->paginateRecords($this->getRefinedQuery());

        /** @var Collection<BaseColumn> */
        $columns = $this->getTableColumns();

        $columnsWithTransforms = $columns->filter(static fn (BaseColumn $column) => $column->canTransformValue());
        $keyName = $this->getKeyName();
        $modelClass = $this->getModelClass();
        $includeOriginalRecordId = config('hybridly.tables.enable_actions') && $columnsWithTransforms->contains(static fn (BaseColumn $column) => $column->getName() === $keyName);
        $columnNames = $columns->map(static fn (BaseColumn $column) => $column->getName());
        $result = $paginatedRecords->through(static fn (Model $record) => [
            // If actions are enabled but the record's key is not included in the
            // columns or is transformed, ensure we still return it because
            // it is needed to identify records when performing actions
            ...($includeOriginalRecordId ? ['__hybridId' => $record->getKey()] : []),

            // Then, we actually include all record attributes that have
            // a column, applying transforms on the way if necessary.
            ...array_filter(
                array: [
                    ...$record->toArray(),
                    ...$columnsWithTransforms->mapWithKeys(static fn (BaseColumn $column) => [
                        $column->getName() => !$column->canTransformValue() ? data_get($record, $column->getName()) : $column->getTransformedValue(
                            named: [
                                'column' => $column,
                                'record' => $record,
                            ],
                            typed: [
                                $modelClass => $record,
                            ],
                        ),
                    ]),
                ],
                callback: fn (string $key) => \in_array($key, [...$columnNames->toArray(), $keyName], true),
                mode: \ARRAY_FILTER_USE_KEY,
            ),
        ]);

        return $this->transformRecords($result);
    }

    protected function transformRecords(Paginator $paginator): Paginator|DataCollectable
    {
        if (isset($this->data) && is_a($this->data, Data::class, allow_string: true)) {
            return $this->data::collection($paginator);
        }

        return $paginator;
    }

    protected function paginateRecords(Builder $query): Paginator
    {
        return $query->paginate(
            pageName: $this->formatScope('page'),
            perPage: $this->getRecordsPerPage(),
        )->withQueryString();
    }

    protected function getRecordsPerPage(): int
    {
        return $this->recordsPerPage ?? 10;
    }

    protected function transformRefinements(Refine $refining): void
    {
        //
    }

    protected function getRefineInstance(): Refine
    {
        if (!$this->refine) {
            $this->refine = Refine::query($this->defineQuery())
                ->scope($this->getScope())
                ->with($this->getRefiners());

            $this->transformRefinements($this->refine);
        }

        return $this->refine->applyRefiners();
    }

    protected function getRefinedQuery(): Builder
    {
        return $this->getRefineInstance()->getBuilderInstance();
    }

    protected function getRefinements(): array
    {
        return $this->getRefineInstance()->refinements();
    }
}
