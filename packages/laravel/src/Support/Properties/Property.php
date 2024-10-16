<?php

namespace Hybridly\Support\Properties;

interface Property
{
    public function __invoke(): mixed;
}
