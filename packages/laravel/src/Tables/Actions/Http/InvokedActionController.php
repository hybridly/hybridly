<?php

namespace Hybridly\Tables\Actions\Http;

use Hybridly\Tables\Actions\BaseAction;
use Hybridly\Tables\Actions\BulkAction;
use Hybridly\Tables\Actions\DataTransferObjects\BulkActionData;
use Hybridly\Tables\Actions\DataTransferObjects\InlineActionData;
use Hybridly\Tables\Actions\DataTransferObjects\InvokedActionData;
use Hybridly\Tables\Actions\InlineAction;
use Hybridly\Tables\InlineTable;
use Hybridly\Tables\Exceptions\CouldNotResolveTableException;
use Hybridly\Tables\Exceptions\InvalidActionException;
use Hybridly\Tables\Exceptions\InvalidActionTypeException;
use Hybridly\Tables\Exceptions\InvalidTableException;
use Hybridly\Tables\Table;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class InvokedActionController
{
    public const INLINE_ACTION = 'action:inline';
    public const BULK_ACTION = 'action:bulk';

    public function __invoke(Request $request): mixed
    {
        $call = InvokedActionData::fromRequest($request);

        return match ($call->type) {
            static::INLINE_ACTION => $this->executeInlineAction(InlineActionData::fromRequest($request)),
            static::BULK_ACTION => $this->executeBulkAction(BulkActionData::fromRequest($request)),
            default => throw InvalidActionTypeException::with($call->type)
        };
    }

    /** @return array{Table,BaseAction} */
    private function resolveAction(InlineActionData|BulkActionData $data): array
    {
        $tableId = Table::decodeId($data->tableId);

        try {
            $table = resolve($tableId);
        } catch (\Throwable) {
            throw CouldNotResolveTableException::with($tableId);
        }

        if (!$table instanceof Table) {
            throw InvalidTableException::with($tableId);
        }

        if ($table instanceof InlineTable) {
            throw InvalidTableException::cannotBeAnonymous();
        }

        $actions = match ($data::class) {
            InlineActionData::class => $table->getInlineActions(showHidden: true),
            BulkActionData::class => $table->getBulkActions(showHidden: true),
        };

        if (!$action = $actions->first(fn (BaseAction $action) => $action->getName() === $data->action)) {
            throw InvalidActionException::with($data->action, $tableId);
        }

        return [$table, $action];
    }

    private function executeInlineAction(InlineActionData $data): mixed
    {
        /**
         * @var Table $table
         * @var InlineAction $action
         */
        [$table, $action] = $this->resolveAction($data);

        $modelClass = $table->getModelClass();
        $record = $action->resolveModel($modelClass, $data);
        $result = $table->evaluate(
            value: $action->getAction(),
            named: [
                'record' => $record,
            ],
            typed: [
                Model::class => $record,
                $table->getModelClass() => $record,
            ],
        );

        if ($result instanceof Response) {
            return $result;
        }

        return back();
    }

    private function executeBulkAction(BulkActionData $data): mixed
    {
        /**
         * @var Table $table
         * @var BulkAction $action
         */
        [$table, $action] = $this->resolveAction($data);

        $model = $table->getModelClass();
        $key = $table->getKeyName();

        /** @var \Illuminate\Database\Eloquent\Builder */
        $query = $table->getRefinedQuery();
        $query = match (true) {
            $data->all === true => $query->whereNotIn($key, $data->except),
            default => $query->whereIn($key, $data->only)
        };

        // If the action has a 'query' parameter, we pass it.
        // Otherwise we execute the query here and pass the result as 'records'.
        $reflection = new \ReflectionFunction($action->getAction());
        $hasRecordsParameter = collect($reflection->getParameters())
            ->some(fn (\ReflectionParameter $parameter) => 'records' === $parameter->getName() || Collection::class === $parameter->getType());

        $result = $table->evaluate(
            value: $action->getAction(),
            named: [
                'query' => $query,
                ...($hasRecordsParameter ? ['records' => $query->get()] : []),
            ],
            typed: [
                Builder::class => $query,
                ...($hasRecordsParameter ? [Collection::class => $query->get()] : []),
            ],
        );

        if ($result instanceof Response) {
            return $result;
        }

        return back();
    }
}
