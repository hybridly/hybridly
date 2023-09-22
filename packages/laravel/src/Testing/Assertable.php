<?php

namespace Hybridly\Testing;

use Hybridly\Hybridly;
use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;
use InvalidArgumentException;
use PHPUnit\Framework\Assert as PHPUnit;
use PHPUnit\Framework\AssertionFailedError;

class Assertable extends AssertableJson
{
    protected string $view;
    protected string $url;
    protected array $payload;
    protected array $properties;
    protected ?array $dialog;
    protected ?string $version;

    public static function fromTestResponse(TestResponse $response): self
    {
        try {
            $response->assertViewHas('payload');
            $payload = json_decode(json_encode($response->viewData('payload')), true);

            PHPUnit::assertIsArray($payload);
            PHPUnit::assertArrayHasKey('view', $payload);
            PHPUnit::assertArrayHasKey('component', $payload['view']);
            PHPUnit::assertArrayHasKey('properties', $payload['view']);
            PHPUnit::assertArrayHasKey('dialog', $payload);
            PHPUnit::assertArrayHasKey('url', $payload);
            PHPUnit::assertArrayHasKey('version', $payload);
        } catch (AssertionFailedError) {
            PHPUnit::fail(sprintf('Not a valid hybrid response: %s', json_encode($payload ?? [], \JSON_PRETTY_PRINT)));
        }

        $instance = static::fromArray($payload);
        $instance->payload = $payload;
        $instance->view = $payload['view']['component'];
        $instance->properties = $payload['view']['properties'];
        $instance->url = $payload['url'];
        $instance->dialog = $payload['dialog'];
        $instance->version = $payload['version'];

        return $instance;
    }

    public function assertViewComponent(string $value = null, bool $shouldExist = null): self
    {
        PHPUnit::assertSame($value, $this->view, 'Unexpected hybrid view component.');

        if ($shouldExist || (\is_null($shouldExist) && config('hybridly.testing.ensure_views_exist', true))) {
            $this->ensureViewExists($value);
        }

        return $this;
    }

    public function assertDialog(array $properties = null, string $view = null, string $baseUrl = null, string $redirectUrl = null): self
    {
        PHPUnit::assertNotNull($this->dialog, 'There is no dialog.');

        if ($baseUrl) {
            PHPUnit::assertSame($baseUrl, $this->dialog['baseUrl'], 'Unexpected dialog base URL.');
        }

        if ($redirectUrl) {
            PHPUnit::assertSame($redirectUrl, $this->dialog['redirectUrl'], 'Unexpected dialog redirect URL.');
        }

        if ($view) {
            PHPUnit::assertSame($view, $this->dialog['component'], 'Unexpected dialog view component.');

            if (config('hybridly.testing.ensure_views_exist', true)) {
                $this->ensureViewExists($view);
            }
        }

        if ($properties) {
            $this->hasProperties($properties, 'dialog.properties');
        }

        return $this;
    }

    public function assertViewUrl(string $value): self
    {
        PHPUnit::assertSame($value, $this->url, 'Unexpected Hybridly view URL.');

        return $this;
    }

    public function assertHybridVersion(string $value): self
    {
        PHPUnit::assertSame($value, $this->version, 'Unexpected Hybridly asset version.');

        return $this;
    }

    public function hasProperties(array $keys, string $scope = null): self
    {
        $scope ??= 'view.properties';
        $properties = data_get($this->payload, $scope);

        foreach ($keys as $key => $value) {
            // ['property_name' => 'property_value'] -> assert that it has the given value
            if (\is_string($key) && \is_string($value)) {
                $this->where($scope . '.' . $key, $value);

                continue;
            }

            // ['property_name'] -> assert that it exists
            if (\is_int($key) && \is_string($value)) {
                $this->has($scope . '.' . $value);

                continue;
            }

            // ['property_name' => 10] -> assert that it exists and has the given count
            if (\is_string($key) && \is_int($value)) {
                // If the value is countable or iterable, assert that it has the given count.
                // If not, assert its exact value
                if (is_countable(data_get($properties, $key)) || is_iterable(data_get($properties, $key))) {
                    $this->has($scope . '.' . $key, $value);
                } else {
                    $this->where($scope . '.' . $key, $value);
                }

                continue;
            }

            // ['property_name' => fn () => ...] -> assert using a callback
            if (\is_string($key) && \is_callable($value)) {
                $firstParameterTypeHint = (new \ReflectionFunction($value))
                    ->getParameters()[0]
                    ->getType()
                    ?->getName();

                if ($firstParameterTypeHint === self::class) {
                    $this->has($scope . '.' . $key, $value);
                } else {
                    $value(data_get($properties, $key));
                }

                continue;
            }

            // ['property_name' => ['foo']] -> assert using an array
            if (\is_string($key) && \is_array($value)) {
                PHPUnit::assertSame($value, data_get($properties, $key));

                continue;
            }

            // ['property_name' => null] -> assert that it's a null value
            if (\is_string($key) && \is_null($value)) {
                $this->where($scope . '.' . $key, null);

                continue;
            }

            // ['property_name' => true] -> assert that it's a bool value
            if (\is_string($key) && \is_bool($value)) {
                $this->where($scope . '.' . $key, $value);

                continue;
            }

            throw new \LogicException("Unknown syntax [{$key} => {$value}]");
        }

        return $this;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getValue(string $key): mixed
    {
        return $this->prop($key);
    }

    public function getProperty(string $key): mixed
    {
        return $this->prop('view.properties.' . $key);
    }

    public function toArray(): array
    {
        return $this->getPayload();
    }

    protected function ensureViewExists(string $identifier): void
    {
        try {
            resolve(Hybridly::class)->getViewFinder()->hasView($identifier);
        } catch (InvalidArgumentException) {
            PHPUnit::fail(sprintf('Hybridly view [%s] is not registered.', $identifier));
        }
    }
}
