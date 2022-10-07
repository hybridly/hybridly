<?php

namespace Monolikit\Testing;

use Closure;
use Illuminate\Testing\TestResponse;

class TestResponseMacros
{
    public function assertMonolikit(): callable
    {
        return function (Closure $callback = null): TestResponse {
            /** @var TestResponse $this */
            $assert = AssertableMonolikit::fromTestResponse($this);

            if (\is_null($callback)) {
                return $this;
            }

            $callback($assert);

            return $this;
        };
    }

    public function monolikitPage(): callable
    {
        return function (): array {
            /** @var TestResponse $this */
            return AssertableMonolikit::fromTestResponse($this)->toArray();
        };
    }
}
