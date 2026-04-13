<?php

namespace Hybridly;

interface Property
{
    /**
     * Evaluates the value of the property.
     */
    public function evaluate(): mixed;
}
