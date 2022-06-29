<?php

namespace Monolikit\View;

use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\JsonResponse;
use Monolikit\Monolikit;
use Monolikit\PropertiesResolver\PropertiesResolver;

class Factory implements Responsable, Renderable
{
    protected View $view;
    protected Payload $payload;

    public function __construct(
        protected Monolikit $monolikit,
        protected PropertiesResolver $resolver,
        protected ResponseFactory $responseFactory,
    ) {
    }

    /**
     * Sets the monolikit view data.
     * @internal This is the same as calling the "monolikit" helper.
     */
    public function view(string $component, array $properties): static
    {
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
            array_merge($this->monolikit->shared(), $this->view->properties),
            $this->monolikit->persisted(),
        );

        $payload = new Payload(
            view: $this->view,
            url: $request->fullUrl(),
            version: $this->monolikit->getVersion(),
            dialog: null, // TODO
        );

        if (monolikit()->isMonolikit($request)) {
            return new JsonResponse(
                data: $payload->toArray(),
                headers: [
                    Monolikit::MONOLIKIT_HEADER => 'true',
                ],
            );
        }

        return $this->responseFactory->view(
            view: $this->monolikit->getRootView(),
            data: ['payload' => $payload->toArray()],
        );
    }
}
