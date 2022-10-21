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
    protected string $url;
    protected array $payload;
    protected array $properties;
    protected ?string $version;
    protected ?string $dialog;

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
            PHPUnit::fail('Not a valid hybrid response.');
        }

        $instance = static::fromArray($payload);
        $instance->payload = $payload;
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

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function getValue(string $key): mixed
    {
        return $this->prop($key);
    }

    public function toArray(): array
    {
        return $this->getPayload();
    }
}
