<?php

namespace Hybridly\Refining\Contracts;

interface Filter
{
    /**
     * Gets the type of the filter. Used by the front-end.
     */
    public function getType(): string;
}
