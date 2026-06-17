<?php

namespace Hybridly\Tables;

use Hybridly\Components;
use Hybridly\Configuration\Configuration;
use Hybridly\MergeTable;
use Illuminate\Contracts\Support\Arrayable;

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

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function merge(bool $prepend = false): MergeTable
    {
        return new MergeTable($this, $prepend);
    }

    public function toArray(): array
    {
        return [
            'id' => self::encodeId($this->getId()),
            'keyName' => $this->getRecordKeyName(),
            'refinements' => $this->getRefinements(),
            'records' => $this->getRecords(),
            'cells' => $this->getCells(),
            'paginator' => $this->getPaginatorMeta(),
            'columns' => $this->getTableColumns()->values(),
            'endpoint' => Configuration::get()->tables->actionsEndpointName,
            'inlineActions' => $this->getRecordKeyName() ? $this->getInlineActions()->values() : [],
            'bulkActions' => $this->getRecordKeyName() ? $this->getBulkActions()->values() : [],
            'scope' => $this->formatScope(),
        ];
    }
}
