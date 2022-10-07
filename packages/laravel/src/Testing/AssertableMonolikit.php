<?php

namespace Monolikit\Testing;

use Illuminate\Testing\Fluent\AssertableJson;
use Illuminate\Testing\TestResponse;
use InvalidArgumentException;
use PHPUnit\Framework\Assert as PHPUnit;
use PHPUnit\Framework\AssertionFailedError;

class AssertableMonolikit extends AssertableJson
{
    /** @var string */
    private $view;

    /** @var string */
    private $url;

    /** @var string|null */
    private $version;

    public static function fromTestResponse(TestResponse $response): self
    {
        try {
            $response->assertViewHas('payload');
            $payload = json_decode(json_encode($response->viewData('payload')), true);

            PHPUnit::assertIsArray($payload);
            PHPUnit::assertArrayHasKey('view', $payload);
            // TODO: Assert view.name and view.properties
            PHPUnit::assertArrayHasKey('dialog', $payload);
            PHPUnit::assertArrayHasKey('url', $payload);
            PHPUnit::assertArrayHasKey('version', $payload);
        } catch (AssertionFailedError $e) {
            PHPUnit::fail('Not a valid Monolikit response.');
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
        PHPUnit::assertSame($value, $this->view, 'Unexpected Monolikit page view.');

        if ($shouldExist || (\is_null($shouldExist) && config('monolikit.testing.ensure_pages_exist', true))) {
            try {
                app('monolikit.testing.view-finder')->find($value);
            } catch (InvalidArgumentException $exception) {
                PHPUnit::fail(sprintf('Monolikit page view file [%s] does not exist.', $value));
            }
        }

        return $this;
    }

    public function url(string $value): self
    {
        PHPUnit::assertSame($value, $this->url, 'Unexpected Monolikit page url.');

        return $this;
    }

    public function version(string $value): self
    {
        PHPUnit::assertSame($value, $this->version, 'Unexpected Monolikit asset version.');

        return $this;
    }

    public function toArray()
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
