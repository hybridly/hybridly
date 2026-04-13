<?php

namespace Hybridly\Tables;

use Hybridly\Components;
use Hybridly\Support\Configuration\Configuration;
use Hybridly\Support\Properties\Scroll;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;

use function Hybridly\scroll;

abstract class Table extends Components\Component implements Arrayable
{
    use Components\Concerns\HasScope;
    use Concerns\HasActions;
    use Concerns\HasColumns;
    use Concerns\HasId;
    use Concerns\HasModel;
    use Concerns\RefinesAndPaginatesRecords;

    public static function make(array $parameters = []): static
    {
        return resolve(static::class, $parameters);
    }

    public static function scroll(array $parameters = []): Scroll
    {
        return scroll(
            value: static fn (): static => static::make($parameters),
            wrapper: 'records',
            metadata: static fn (Request $request, self $value): TableScrollMetadata => TableScrollMetadata::fromTable($request, $value),
        );
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => self::encodeId($this->getId()),
            'keyName' => $this->getKeyName(),
            'refinements' => $this->getRefinements(),
            'records' => $this->getRecords(),
            'paginator' => $this->getPaginatorMeta(),
            'columns' => $this->getTableColumns()->values(),
            'endpoint' => Configuration::get()->tables->actionsEndpointName,
            'inlineActions' => $this->getInlineActions()->values(),
            'bulkActions' => $this->getBulkActions()->values(),
            'scope' => $this->formatScope(),
        ];
    }

    public function toArray()
    {
        return $this->jsonSerialize();
    }
}
