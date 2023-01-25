<?php

namespace Hybridly\View;

use Hybridly\Contracts\HybridResponse;
use Hybridly\DialogResolver;
use Hybridly\Hybridly;
use Hybridly\PropertiesResolver\PropertiesResolver;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelData\Contracts\DataObject;

class Factory implements HybridResponse
{
    protected View $view;
    protected View $dialogView;
    protected ?string $dialogBaseUrl = null;

    public function __construct(
        protected Hybridly $hybridly,
        protected Router $router,
        protected DialogResolver $dialogResolver,
        protected ResponseFactory $responseFactory,
    ) {
    }

    /**
     * Sets the base route for this view, implying a dialog will be rendered.
     */
    public function base(string $route, mixed $parameters = null): static
    {
        // In order to provide autocompletion support without adding
        // a `baseUrl` method, we check if `$route` is a named
        // route, in which case we call `route` on it.

        $this->dialogBaseUrl = Route::has($route)
            ? route($route, $parameters)
            : $route;

        return $this;
    }

    /**
     * Sets the hybridly view data.
     */
    public function view(string $component, array|Arrayable|DataObject $properties): static
    {
        if ($properties instanceof Arrayable || $properties instanceof DataObject) {
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

    protected function resolveDialog(Request $request): ?Dialog
    {
        if (!$this->dialogBaseUrl) {
            return null;
        }

        return $this->dialogResolver->resolve(
            baseUrl: $this->dialogBaseUrl,
            request: $request,
            view: new View(
                component: $this->view->component,
                properties: Arr::except($this->view->properties, array_keys($this->hybridly->shared())),
            ),
        );
    }

    public function toResponse($request)
    {
        // We don't use dependency injection, because the request object
        // could be different than the one given to `toResponse`.
        $resolver = resolve(PropertiesResolver::class, ['request' => $request]);

        // Properties need to be resolved because they can be impacted
        // in multiple ways. We use a resolver to do so and mutate
        // the view, which will be serialized in the payload.
        $this->view->properties = $resolver->resolve(
            $this->view->component,
            array_merge($this->hybridly->shared(), $this->view->properties),
            $this->hybridly->persisted(),
        );

        $payload = new Payload(
            view: $this->view,
            dialog: $this->resolveDialog($request),
            url: $request->fullUrl(),
            version: $this->hybridly->getVersion(),
        );

        if ($payload->dialog) {
            $payload = $this->renderDialog($request, $payload);
        }

        event('hybridly.response', [
            [
                'payload' => $payload->toArray(),
                'request' => $request,
                'version' => $this->hybridly->getVersion(),
                'root_view' => $this->hybridly->getRootView(),
            ],
        ]);

        if ($this->hybridly->isHybrid($request)) {
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

    protected function renderDialog(Request $request, Payload $payload)
    {
        // TODO the context thing
        $kernel = app()->make(Kernel::class);
        $url = $payload->dialog->redirectUrl;

        do {
            $response = $kernel->handle(
                $this->createBaseRequest($request, $payload, $url),
            );

            if (!$response->headers->get(Hybridly::HYBRIDLY_HEADER) && !$response->isRedirect()) {
                return $response;
            }

            $url = $response->isRedirect() ? $response->getTargetUrl() : null;
        } while ($url);

        app()->instance('request', $request);

        $basePayload = $response->getData(true);

        return new Payload(
            view: new View(
                component: $basePayload['view']['component'],
                properties: $basePayload['view']['properties'],
            ),
            url: $payload->url,
            version: $payload->version,
            dialog: $payload->dialog,
        );
    }

    protected function createBaseRequest(Request $request, Payload $payload, string $url)
    {
        $baseRequest = Request::create($url, Request::METHOD_GET);
        $baseRequest->headers->replace([
            ...$request->headers->all(),
            'Accept' => 'text/html, application/xhtml+xml',
            'X-Requested-With' => 'XMLHttpRequest',
            Hybridly::HYBRIDLY_HEADER => true,
            Hybridly::VERSION_HEADER => $payload->version,
        ]);

        return $baseRequest;
    }
}
