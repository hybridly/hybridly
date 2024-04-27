<?php

namespace Hybridly\Frame;

use Hybridly\Contracts\HybridResponse;
use Hybridly\Exceptions\MustBeHybridException;
use Hybridly\Hybridly;
use Hybridly\Support\Concerns\TransformsProperties;
use Hybridly\Support\Header;
use Hybridly\View\PropertiesResolver;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\LaravelData\Contracts\TransformableData;

final class Factory implements HybridResponse
{
    use TransformsProperties;

    public const RESPONSE_EVENT = 'hybridly.response.frame';

    protected ?Frame $frame = null;

    public function __construct(
        protected Hybridly $hybridly,
    ) {
    }

    /**
     * Sets the hybridly frame data.
     */
    public function frame(string $component = null, array|Arrayable|TransformableData $properties = []): static
    {
        $this->frame = new Frame(
            component: $component,
            properties: $this->transformProperties($properties),
        );

        return $this;
    }

    public function toResponse($request)
    {
        event(self::RESPONSE_EVENT, [[
            'frame' => $this->frame->toArray(),
            'request' => $request,
        ]]);

        if (!$this->hybridly->isHybrid($request)) {
            throw MustBeHybridException::make();
        }

        return new JsonResponse(
            data: $this->frame->toArray(),
            headers: [
                Header::HYBRID_REQUEST => 'true',
            ],
        );
    }

    /**
     * Renders the response content.
     */
    public function render(): string|false
    {
        return $this
            ->toResponse(request())
            ->getContent();
    }

    /**
     * Resolves the properties on the given view or dialog.
     */
    protected function resolveProperties(Frame $frame, Request $request): array
    {
        // We don't use dependency injection, because the request object
        // could be different than the one given to `toResponse`.
        $resolver = resolve(PropertiesResolver::class, ['request' => $request]);

        return $resolver->resolve(
            component: $frame->component,
            properties: $frame->properties,
            persisted: $this->hybridly->persisted(),
        );
    }
}
