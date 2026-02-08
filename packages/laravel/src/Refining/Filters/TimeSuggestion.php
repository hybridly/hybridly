<?php

namespace Hybridly\Refining\Filters;

use Carbon\CarbonInterface;
use JsonSerializable;

final class TimeSuggestion implements JsonSerializable
{
    public function __construct(
        public string $label,
        public CarbonInterface $date,
    ) {}

    public function jsonSerialize(): mixed
    {
        return [
            'type' => 'time',
            'label' => $this->label,
            'date' => $this->date->toIso8601String(),
        ];
    }
}
