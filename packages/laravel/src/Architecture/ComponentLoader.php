<?php

namespace Hybridly\Architecture;

interface ComponentLoader
{
    /** @return Component[] */
    public function load(): array;
}
