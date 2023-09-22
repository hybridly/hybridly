<?php

namespace Hybridly\Tables\Concerns;

use Hybridly\Refining\Contracts\Refiner;
use Hybridly\Refining\Refine;
use Hybridly\Tables\Columns\BaseColumn;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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

    public function getRefiners(): Collection
    {
        return $this->cachedRefinements ??= collect($this->defineRefiners())
            ->filter(static fn (Refiner $refiner): bool => !$refiner->isHidden());
    }

    protected function defineRefiners(): array
    {
        return [];
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
                'links' => $pagination['links'] ?? [],
                'meta' => array_filter([
                    'current_page' => $pagination['current_page'] ?? null,
                    'first_page_url' => $pagination['first_page_url'] ?? null,
                    'from' => $pagination['from'] ?? null,
                    'last_page' => $pagination['last_page'] ?? null,
                    'last_page_url' => $pagination['last_page_url'] ?? null,
                    'next_page_url' => $pagination['next_page_url'] ?? null,
                    'path' => $pagination['path'] ?? null,
                    'per_page' => $pagination['per_page'] ?? null,
                    'prev_page_url' => $pagination['prev_page_url'] ?? null,
                    'to' => $pagination['to'] ?? null,
                    'total' => $pagination['total'] ?? null,
                    'prev_cursor' => $pagination['prev_cursor'] ?? null,
                    'next_cursor' => $pagination['next_cursor'] ?? null,
                ]),
            ];
        }

        return [
            'links' => $pagination['links'],
            'meta' => $pagination['meta'],
        ];
    }

    protected function transformRecords(Paginator|CursorPaginator $paginator): Paginator|CursorPaginator|DataCollectable
    {
        if (isset($this->data) && is_a($this->data, Data::class, allow_string: true)) {
            return $this->data::collection($paginator);
        }

        return $paginator;
    }

    /**
     * Determines how the query will be paginated.
     */
    protected function paginateRecords(Builder $query): Paginator|CursorPaginator
    {
        $paginator = match ($this->getPaginatorType()) {
            LengthAwarePaginator::class => $query->paginate(
                perPage: $this->getRecordsPerPage(),
                pageName: $this->formatScope('page'),
            ),
            Paginator::class => $query->simplePaginate(
                perPage: $this->getRecordsPerPage(),
                pageName: $this->formatScope('page'),
            ),
            CursorPaginator::class => $query->cursorPaginate(
                perPage: $this->getRecordsPerPage(),
                cursorName: $this->formatScope('cursor'),
            ),
            default => throw new \Exception("Invalid paginator type [{$this->getPaginatorType()}]"),
        };

        return $paginator->withQueryString();
    }

    /**
     * Defines the kind of pagination that will be used for this table.
     */
    protected function getPaginatorType(): string
    {
        return $this->paginatorType ?? LengthAwarePaginator::class;
    }

    /**
     * Defines the amount of records per page for this table.
     */
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

    private function getPaginatedRecords(): array
    {
        return $this->cachedRecords ??= $this->transformPaginatedRecords()->toArray();
    }

    private function transformPaginatedRecords(): Paginator|CursorPaginator|DataCollectable
    {
        $paginatedRecords = $this->paginateRecords($this->getRefinedQuery());

        /** @var Collection<BaseColumn> */
        $columns = $this->getTableColumns();

        $columnsWithTransforms = $columns->filter(static fn (BaseColumn $column) => $column->canTransformValue());
        $keyName = $this->getKeyName();
        $modelClass = $this->getModelClass();
        $includeOriginalRecordId = config('hybridly.tables.enable_actions') !== false && $columnsWithTransforms->contains(static fn (BaseColumn $column) => $column->getName() === $keyName);
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
}
