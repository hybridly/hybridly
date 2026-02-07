<?php

namespace Hybridly\Refining\Concerns;

use Hybridly\Refining\Refine;

trait HasRefineInstance
{
    protected Refine $refine;

    public function setRefineInstance(Refine $refine): void
    {
        $this->refine = $refine;
    }

    public function getRefineInstance(): Refine
    {
        return $this->refine;
    }
}
