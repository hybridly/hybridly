<?php

namespace Hybridly\Tables;

use Hybridly\Components;
use Hybridly\Support\Configuration\Configuration;

abstract class Table extends Components\Component
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
}
