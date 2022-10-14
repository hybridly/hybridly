<?php

namespace Hybridly\Testing;

use Closure;
use Illuminate\Testing\TestResponse;

class TestResponseMacros
{
    /**
     * Dump Hybridly's response and die.
     *
     * @return Closure
     */
    public function hdd(): Closure
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

    public function assertHybridly(): Closure
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

    public function assertHybridlyView(): Closure
    {
        return function (string $view): TestResponse {
            /** @var TestResponse $this */
            Assertable::fromTestResponse($this)->view($view);

            return $this;
        };
    }

    public function assertHybridlyProperty(): Closure
    {
        return function (string $key, $length = null, \Closure $callback = null): TestResponse {
            /** @var TestResponse $this */
            Assertable::fromTestResponse($this)->hasProperty($key, $length, $callback);

            return $this;
        };
    }

    public function assertHybridlyProperties(): Closure
    {
        return function (array $keys): TestResponse {
            /** @var TestResponse $this */
            Assertable::fromTestResponse($this)->hasProperties($keys);

            return $this;
        };
    }
}
