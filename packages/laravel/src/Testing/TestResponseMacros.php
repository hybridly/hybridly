<?php

namespace Hybridly\Testing;

use Closure;
use Illuminate\Testing\TestResponse;

class TestResponseMacros
{
    /**
     * Dump hybrid response and die.
     *
     * @return Closure
     */
    public function hdd(): Closure
    {
        return function (null|string|int $path = null): TestResponse {
            /** @var TestResponse $this */
            try {
                $response = Assertable::fromTestResponse($this);

                if (!\is_null($path)) {
                    dd($response->getProperty($path));
                }

                dd($response);
            } catch (\Throwable $th) {
                $this->dd();
            }
        };
    }

    public function assertHybrid(): Closure
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

    public function assertHybridView(): Closure
    {
        return function (string $view): TestResponse {
            /** @var TestResponse $this */
            Assertable::fromTestResponse($this)->view($view);

            return $this;
        };
    }

    public function assertHybridProperty(): Closure
    {
        return function (string $key, $length = null, \Closure $callback = null): TestResponse {
            /** @var TestResponse $this */
            Assertable::fromTestResponse($this)->hasProperty($key, $length, $callback);

            return $this;
        };
    }

    public function assertHybridProperties(): Closure
    {
        return function (array $keys): TestResponse {
            /** @var TestResponse $this */
            Assertable::fromTestResponse($this)->hasProperties($keys);

            return $this;
        };
    }
}
