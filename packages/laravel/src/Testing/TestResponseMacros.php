<?php

namespace Monolikit\Testing;

use Closure;
use Illuminate\Testing\TestResponse;

class TestResponseMacros
{
    public function assertMonolikit(): Closure
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

    public function assertMonolikitView(): Closure
    {
        return function (string $view): TestResponse {
            /** @var TestResponse $this */
            AssertableMonolikit::fromTestResponse($this)->view($view);

            return $this;
        };
    }

    public function hasMonolikitProperty(): Closure
    {
        return function (string $key, $length = null, \Closure $callback = null): TestResponse {
            /** @var TestResponse $this */
            AssertableMonolikit::fromTestResponse($this)->hasProperty($key, $length, $callback);

            return $this;
        };
    }
}
