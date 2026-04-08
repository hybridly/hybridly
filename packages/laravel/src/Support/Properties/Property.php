<?php

namespace Hybridly\Support\Properties;

interface Property
{
    /**
     * Evaluates the value of the property.
     */
    public function evaluate(): mixed;
}
