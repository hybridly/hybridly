<?php

namespace Hybridly\Refining\Concerns;

use Illuminate\Http\Request;

trait HasRequest
{
    protected Request $request;

    protected function setRequest(Request $request): static
    {
        $this->request = $request;

        return $this;
    }

    protected function getRequest(): Request
    {
        return $this->request;
    }
}
