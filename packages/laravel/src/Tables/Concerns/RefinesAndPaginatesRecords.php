<?php

namespace Hybridly\Tables\Concerns;

use Hybridly\Configuration\Configuration;
use Hybridly\Refining\Contracts\Refiner;
use Hybridly\Refining\Refine;
use Hybridly\Tables\Columns\BaseColumn;
use Hybridly\Tables\Table;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\LaravelData\Contracts\BaseDataCollectable;
use Spatie\LaravelData\Data;

use function Hybridly\Support\resolve_arrayable_properties;

/**
 * @mixin Table
 * @mixin HasColumns
 */
trait RefinesAndPaginatesRecords
{
    private ?Refine $refine = null;
    private mixed $cachedRecords = null;
    private mixed $cachedRefiners = null;
    private mixed $cachedRows = null;

    public function getRefiners(): Collection
    {
        return $this->cachedRefiners ??= collect($this->defineRefiners())
            ->filter(static fn (Refiner $refiner): bool => ! $refiner->isHidden());
    }

    public function getRecords(): array
    {
        return $this
            ->getRows()
            ->pluck('record')
            ->all();
    }

    public function getCells(): array
    {
        return $this
            ->getRows()
            ->pluck('cells')
            ->all();
    }

    public function getRecordKeyName(): ?string
    {
        $keyName = $this->getKeyName();

        if ($this->getRows()->contains(fn (array $row) => \is_scalar(data_get($row['record'], $keyName)))) {
            return $keyName;
        }

        return null;
    }

    public function getRefinedQuery(): Builder
    {
        return $this->getRefineInstance()->getBuilderInstance();
    }

    /**
     * Disables authorization resolving.
     */
    public function withoutResolvingAuthorizations(): static
    {
        $this->resolvesAuthorizations = false;

        return $this;
    }

    protected function defineRefiners(): array
    {
        return [];
    }

    protected function getPaginatorMeta(): array
    {
        $pagination = $this->getPaginatedRecords();

        // Wraps pagination data if necessary
        if (! \array_key_exists('meta', $pagination)) {
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

    protected function getRequest(): Request
    {
        return $this->getRefineInstance()->getRequest();
    }

    protected function transformRecords(Paginator|CursorPaginator $paginator): Paginator|CursorPaginator|BaseDataCollectable
    {
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
        return $this->recordsPerPage ?? Configuration::get()->tables->recordsPerPage;
    }

    protected function transformRefinements(Refine $refining): void
    {
    }

    protected function getRefineInstance(): Refine
    {
        if (! $this->refine) {
            $this->refine = Refine::query($this->defineQuery())
                ->scope($this->getScope())
                ->with($this->getRefiners());

            $this->transformRefinements($this->refine);
        }

        return $this->refine->applyRefiners();
    }

    protected function getRefinements(): array
    {
        return $this->getRefineInstance()->refinements();
    }

    protected function getRecordArrayFromModel(Model $model): array
    {
        if (isset($this->data) && is_a($this->data, Data::class, allow_string: true)) {
            $record = $this->resolveDataRecord($model);

            if (! $this->resolvesAuthorizations()) {
                $record->excludePermanently('authorization');
            }

            return resolve_arrayable_properties($record->toArray());
        }

        return $model->toArray();
    }

    protected function resolveDataRecord(Model $model): Data
    {
        return $this->data::from($model);
    }

    /**
     * Determines whether authorizations should be resolved.
     */
    protected function resolvesAuthorizations(): bool
    {
        return $this->resolvesAuthorizations ?? true;
    }

    private function getPaginatedRecords(): array
    {
        return $this->cachedRecords ??= $this->transformPaginatedRecords()->toArray();
    }

    private function transformPaginatedRecords(): Paginator|CursorPaginator|BaseDataCollectable
    {
        $paginatedRecords = $this->paginateRecords($this->getRefinedQuery());

        return $paginatedRecords->through(function (Model $model) {
            $record = $this->getRecordArrayFromModel($model);

            return [
                'record' => $record,
                'cells' => [
                    'key' => $this->getRecordKey($record),
                    'columns' => $this->getRecordCells($record, $model),
                ],
            ];
        });
    }

    /**
     * @return Collection<int,array{record: array, cells: array{key: string|int|null, columns: array}}>
     */
    private function getRows(): Collection
    {
        return $this->cachedRows ??= collect(data_get($this->getPaginatedRecords(), 'data', []))
            ->values();
    }

    private function getRecordKey(array $record): int|string|null
    {
        $key = data_get($record, $this->getKeyName());

        return \is_scalar($key) ? $key : null;
    }

    private function getRecordCells(array $record, Model $model): array
    {
        return $this
            ->getTableColumns()
            ->mapWithKeys(function (BaseColumn $column) use ($model, $record) {
                $key = $column->getName();
                $value = data_get($record, $key);

                return [
                    $key => [
                        'extra' => ! $column->hasExtra()
                            ? []
                            : $column->getExtra(
                                named: [
                                    'record' => $model,
                                    'model' => $model,
                                ],
                                typed: [
                                    $this->getModelClass() => $model,
                                ],
                            ),
                        'value' => ! $column->canTransformValue()
                            ? $value
                            : $column->getTransformedValue(
                                named: [
                                    'column' => $column,
                                    'record' => $record,
                                ],
                                typed: [
                                    $this->getModelClass() => $model,
                                ],
                            ),
                    ],
                ];
            })
            ->all();
    }
}
