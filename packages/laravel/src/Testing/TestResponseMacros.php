<?php

namespace Monolikit\Testing;

use Closure;

class TestResponseMacros
{
    public function assertMonolikit()
    {
        return function (Closure $callback = null) {
            $assert = AssertableMonolikit::fromTestResponse($this);

            if (\is_null($callback)) {
                return $this;
            }

            $callback($assert);

            return $this;
        };
    }

    public function monolikitPage()
    {
        return function () {
            return AssertableMonolikit::fromTestResponse($this)->toArray();
        };
    }
}
