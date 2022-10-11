<?php

namespace Hybridly\View;

use Hybridly\Hybridly;
use Hybridly\PropertiesResolver\PropertiesResolver;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;

class Factory implements Responsable, Renderable
{
    protected View $view;
    protected Payload $payload;

    public function __construct(
        protected Hybridly $hybridly,
        protected PropertiesResolver $resolver,
        protected ResponseFactory $responseFactory,
    ) {
    }

    /**
     * Sets the hybridly view data.
     */
    public function view(string $component, array|Arrayable $properties): static
    {
        if ($properties instanceof Arrayable) {
            $properties = $properties->toArray();
        }

        $this->view = new View(
            $component,
            $properties,
        );

        return $this;
    }

    /**
     * Adds properties to the view.
     */
    public function with(array|string $key, mixed $value = null): static
    {
        if (\is_array($key)) {
            $this->view->properties = array_merge($this->view->properties, $key);
        } else {
            $this->view->properties[$key] = $value;
        }

        return $this;
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

    public function toResponse($request)
    {
        // Properties need to be resolved because they can be impacted
        // in multiple ways. We use a resolver to do so and mutate
        // the view, which will be serialized in the payload.
        $this->view->properties = $this->resolver->resolve(
            $this->view->component,
            array_merge($this->hybridly->shared(), $this->view->properties),
            $this->hybridly->persisted(),
        );

        $payload = new Payload(
            view: $this->view,
            url: $request->fullUrl(),
            version: $this->hybridly->getVersion(),
            dialog: null, // TODO
        );

        event('hybridly.response', [
            [
                'payload' => $payload->toArray(),
                'request' => $request,
                'version' => $this->hybridly->getVersion(),
                'root_view' => $this->hybridly->getRootView(),
            ],
        ]);

        if (hybridly()->isHybridly($request)) {
            return new JsonResponse(
                data: $payload->toArray(),
                headers: [
                    Hybridly::HYBRIDLY_HEADER => 'true',
                ],
            );
        }

        return $this->responseFactory->view(
            view: $this->hybridly->getRootView(),
            data: ['payload' => $payload->toArray()],
        );
    }
}
