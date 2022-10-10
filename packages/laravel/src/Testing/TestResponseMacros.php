<?php

namespace Monolikit\Testing;

use Closure;
use Illuminate\Testing\TestResponse;

class TestResponseMacros
{
    public function mdd(): Closure
    {
        return function (): TestResponse {
            /** @var TestResponse $this */
            try {
                dd(Assertable::fromTestResponse($this));
            } catch (\Throwable $th) {
                $this->dd();
            }
        };
    }

    public function assertMonolikit(): Closure
    {
        return function (Closure $callback = null): TestResponse {
            /** @var TestResponse $this */
            $assert = Assertable::fromTestResponse($this);

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
            Assertable::fromTestResponse($this)->view($view);

            return $this;
        };
    }

    public function assertMonolikitProperty(): Closure
    {
        return function (string $key, $length = null, \Closure $callback = null): TestResponse {
            /** @var TestResponse $this */
            Assertable::fromTestResponse($this)->hasProperty($key, $length, $callback);

            return $this;
        };
    }

    public function assertMonolikitProperties(): Closure
    {
        return function (array $keys): TestResponse {
            /** @var TestResponse $this */
            Assertable::fromTestResponse($this)->hasProperties($keys);

            return $this;
        };
    }
}
