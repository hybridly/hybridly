<?php

namespace Hybridly\Testing;

use Closure;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Assert as PHPUnit;
use PHPUnit\Framework\AssertionFailedError;

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
                    dd($response->getValue('view.properties.' . $path) ?? $response->toArray());
                }

                dd($response->toArray());
            } catch (\Throwable) {
                $this->dd();
            }
        };
    }

    public function assertNotHybrid(): Closure
    {
        return function (): TestResponse {
            /** @var TestResponse $this */
            try {
                Assertable::fromTestResponse($this);
            } catch (AssertionFailedError) {
                Assert::assertTrue(true);

                return $this;
            }

            PHPUnit::fail("The response is hybrid while it's expected not to be.");
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

    /**
     * Asserts that the given hybrid property exists.
     */
    public function assertHasHybridProperty(): Closure
    {
        return function (string $key, $length = null, \Closure $callback = null): TestResponse {
            /** @var TestResponse $this */
            Assertable::fromTestResponse($this)->has('view.properties.' . $key, $length, $callback);

            return $this;
        };
    }

    /**
     * Asserts that the given hybrid property is missing.
     */
    public function assertMissingHybridProperty(): Closure
    {
        return function (string $key, $length = null, \Closure $callback = null): TestResponse {
            /** @var TestResponse $this */
            Assertable::fromTestResponse($this)->missing('view.properties.' . $key, $length, $callback);

            return $this;
        };
    }

    /**
     * Asserts that the property at the given path has the expected value.
     */
    public function assertHybridProperty(): Closure
    {
        return function (string|int $key, mixed $expected): TestResponse {
            /** @var TestResponse $this */
            Assertable::fromTestResponse($this)->where('view.properties.' . $key, $expected);

            return $this;
        };
    }

    /**
     * Asserts that the given hybrid properties exist.
     */
    public function assertHybridProperties(): Closure
    {
        return function (array $keys): TestResponse {
            /** @var TestResponse $this */
            Assertable::fromTestResponse($this)->hasProperties($keys);

            return $this;
        };
    }

    /**
     * Asserts that the payload property at the given path has the expected value.
     */
    public function assertHybridPayload(): Closure
    {
        return function (string|int $key, mixed $expected): TestResponse {
            /** @var TestResponse $this */
            Assertable::fromTestResponse($this)->where($key, $expected);

            return $this;
        };
    }

    /**
     * Asserts that the hybrid response's view is the expected value.
     */
    public function assertHybridView(): Closure
    {
        return function (string $view): TestResponse {
            /** @var TestResponse $this */
            Assertable::fromTestResponse($this)->view($view);

            return $this;
        };
    }

    /**
     * Asserts that the hybrid response's dialog view is the expected value.
     */
    public function assertHybridDialogView(): Closure
    {
        return function (string $dialogView, ?string $baseUrl = null): TestResponse {
            /** @var TestResponse $this */
            Assertable::fromTestResponse($this)->dialogView($dialogView, $baseUrl);

            return $this;
        };
    }

    /**
     * Asserts that the hybrid response's version is the expected value.
     */
    public function assertHybridVersion(): Closure
    {
        return function (string $version): TestResponse {
            /** @var TestResponse $this */
            Assertable::fromTestResponse($this)->version($version);

            return $this;
        };
    }

    /**
     * Asserts that the hybrid response's url is the expected value.
     */
    public function assertHybridUrl(): Closure
    {
        return function (string $url): TestResponse {
            /** @var TestResponse $this */
            Assertable::fromTestResponse($this)->url($url);

            return $this;
        };
    }

    /**
     * Returns the hybrid payload as an array.
     */
    public function getHybridPayload(): Closure
    {
        return function (): array {
            /** @var TestResponse $this */
            return Assertable::fromTestResponse($this)->toArray();
        };
    }

    /**
     * Returns the given hybrid property as an array.
     */
    public function getHybridProperty(): Closure
    {
        return function (string $key): mixed {
            /** @var TestResponse $this */
            return Assertable::fromTestResponse($this)->getProperty($key);
        };
    }
}
