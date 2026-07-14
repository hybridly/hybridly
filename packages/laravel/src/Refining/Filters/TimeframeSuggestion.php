<?php

namespace Hybridly\Refining\Filters;

use Carbon\CarbonInterface;
use JsonSerializable;

final class TimeframeSuggestion implements JsonSerializable
{
    public function __construct(
        public string $label,
        public CarbonInterface $start,
        public CarbonInterface $end,
        public ?string $key = null,
    ) {}

    public function jsonSerialize(): mixed
    {
        return [
            'type' => 'timeframe',
            'label' => $this->label,
            'start' => $this->start->toIso8601String(),
            'end' => $this->end->toIso8601String(),
            'key' => $this->key,
        ];
    }
}
