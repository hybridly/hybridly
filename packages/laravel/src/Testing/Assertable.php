<?php

namespace Hybridly\Testing;

use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;
use InvalidArgumentException;
use PHPUnit\Framework\Assert as PHPUnit;
use PHPUnit\Framework\AssertionFailedError;

class Assertable extends AssertableJson
{
    protected string $view;
    protected ?string $dialog;
    protected string $url;
    protected ?string $version;

    public static function fromTestResponse(TestResponse $response): self
    {
        try {
            $response->assertViewHas('payload');
            $payload = json_decode(json_encode($response->viewData('payload')), true);

            PHPUnit::assertIsArray($payload);
            PHPUnit::assertArrayHasKey('view', $payload);
            PHPUnit::assertArrayHasKey('name', $payload['view']);
            PHPUnit::assertArrayHasKey('properties', $payload['view']);
            PHPUnit::assertArrayHasKey('dialog', $payload);
            PHPUnit::assertArrayHasKey('url', $payload);
            PHPUnit::assertArrayHasKey('version', $payload);
        } catch (AssertionFailedError) {
            PHPUnit::fail('Not a valid Hybridly response.');
        }

        $instance = static::fromArray($payload['view']);
        $instance->view = $payload['view']['name'];
        $instance->properties = $payload['view']['properties'];
        $instance->dialog = $payload['dialog'];
        $instance->url = $payload['url'];
        $instance->version = $payload['version'];

        return $instance;
    }

    public function view(string $value = null, $shouldExist = null): self
    {
        PHPUnit::assertSame($value, $this->view, 'Unexpected Hybridly page view.');

        if ($shouldExist || (\is_null($shouldExist) && config('hybridly.testing.ensure_pages_exist', true))) {
            try {
                app('hybridly.testing.view_finder')->find($value);
            } catch (InvalidArgumentException) {
                PHPUnit::fail(sprintf('Hybridly page view file [%s] does not exist.', $value));
            }
        }

        return $this;
    }

    /**
     * Gets the given hybrid property.
     */
    public function getProperty(string|int $key): mixed
    {
        return $this->prop('properties.' . (string) $key);
    }

    /**
     * Ensures the given property exists. Dot-notation is supported.
     */
    public function hasProperty(string|int $key, int|\Closure|null $length = null, \Closure|null $callback = null): self
    {
        return $this->has('properties.' . (string) $key, $length, $callback);
    }

    public function hasProperties(array $keys): self
    {
        foreach ($keys as $key => $value) {
            if (\is_int($key) && \is_string($value)) {
                $this->hasProperty($value);
            }
            if (\is_string($key) && \is_int($value)) {
                $this->hasProperty($key, $value);
            }
            $this->hasProperty($key, null, $value);
        }

        return $this;
    }

    public function url(string $value): self
    {
        PHPUnit::assertSame($value, $this->url, 'Unexpected Hybridly page url.');

        return $this;
    }

    public function version(string $value): self
    {
        PHPUnit::assertSame($value, $this->version, 'Unexpected Hybridly asset version.');

        return $this;
    }

    public function toArray(): array
    {
        return [
            'view' => $this->view,
            'properties' => $this->property(),
            'dialog' => $this->dialog,
            'url' => $this->url,
            'version' => $this->version,
        ];
    }
}
