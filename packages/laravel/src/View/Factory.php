<?php

namespace Hybridly\View;

use Hybridly\Contracts\HybridResponse;
use Hybridly\Hybridly;
use Hybridly\Support\DialogResolver;
use Hybridly\Support\Header;
use Hybridly\Support\MissingViewComponentException;
use Hybridly\Support\PropertiesResolver;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Route;
use Spatie\LaravelData\Contracts\DataObject;
use Symfony\Component\HttpFoundation\RedirectResponse;

class Factory implements HybridResponse
{
    protected View $view;
    protected View $dialogView;
    protected ?string $dialogBaseUrl = null;

    public const RESPONSE_EVENT = 'hybridly.response';

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
    public function view(string $component = null, array|Arrayable|DataObject $properties = []): static
    {
        $this->view = new View(
            component: $component,
            properties: $this->transformProperties($properties),
        );

        return $this;
    }

    /**
     * Sets the view component.
     */
    public function component(string $component): static
    {
        $this->view = new View(
            component: $component,
            properties: $this->view?->properties ?? [],
        );

        return $this;
    }

    /**
     * Sets the view properties.
     */
    public function properties(array|Arrayable|DataObject $properties): static
    {
        $this->view = new View(
            component: $this->view?->component,
            properties: $this->transformProperties($properties),
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
        $payload = new Payload(
            view: $this->resolveView($this->view, $request),
            dialog: $this->resolveDialog($request),
            url: $this->resolveUrl($request),
            version: $this->hybridly->getVersion(),
        );

        if ($payload->dialog) {
            $payload = $this->renderDialog($request, $payload);
        }

        event(self::RESPONSE_EVENT, [[
            'payload' => $payload->toArray(),
            'request' => $request,
            'version' => $this->hybridly->getVersion(),
            'root_view' => $this->hybridly->getRootView(),
        ]]);

        // If the component is missing and there is no page loaded,
        // throw an exception because the front-end cannot handle that situation.
        if (!$this->hybridly->isHybrid($request) && !$this->view->component) {
            throw MissingViewComponentException::make();
        }

        if ($this->hybridly->isHybrid($request)) {
            return new JsonResponse(
                data: $payload->toArray(),
                headers: [
                    Header::HYBRID_REQUEST => 'true',
                ],
            );
        }

        return $this->responseFactory->view(
            view: $this->hybridly->getRootView(),
            data: ['payload' => $payload->toArray()],
        );
    }

    protected function transformProperties(array|Arrayable|DataObject $properties): array
    {
        if ($properties instanceof Arrayable || $properties instanceof DataObject) {
            $properties = $properties->toArray();
        }

        return $properties;
    }

    protected function renderDialog(Request $request, Payload $payload)
    {
        return new Payload(
            view: $this->getBaseView($payload->dialog->redirectUrl, $request),
            url: $payload->url,
            version: $payload->version,
            dialog: new Dialog(
                component: $payload->dialog->component,
                properties: $this->resolveProperties($payload->dialog, $request),
                baseUrl: $payload->dialog->baseUrl,
                redirectUrl: $payload->dialog->redirectUrl,
                key: $payload->dialog->key,
            ),
        );
    }

    /**
     * Gets the base view for the given URL.
     */
    protected function getBaseView(string $targetUrl, Request $originalRequest): View
    {
        $request = Request::create(
            uri: $targetUrl,
            method: Request::METHOD_GET,
            parameters: $originalRequest->query->all(),
            cookies: $originalRequest->cookies->all(),
            files: $originalRequest->files->all(),
            server: $originalRequest->server->all(),
            content: $originalRequest->getContent(),
        );

        $route = $this->router->getRoutes()->match($request);

        /** @var array */
        $originalHeaders = $originalRequest->headers->all();
        $request->headers->replace($originalHeaders);
        $request->setJson($originalRequest->json());
        $request->setUserResolver(fn () => $originalRequest->getUserResolver());
        $request->setRouteResolver(fn () => $route);

        if ($originalRequest->hasSession() && $session = $originalRequest->session()) {
            $request->setLaravelSession($session);
        }

        app()->instance('request', $request);

        $response = (new SubstituteBindings($this->router))->handle(
            request: $request,
            next: fn () => $route->run(),
        );

        if ($response instanceof RedirectResponse) {
            return $this->getBaseView($response->getTargetUrl(), $request);
        }

        if (!$response instanceof self) {
            throw new \LogicException(sprintf('Target URL [%s] does not return a hybrid response.', $targetUrl));
        }

        return $this->resolveView($response->view, $request);
    }

    /**
     * Resolves the dialog from the request.
     */
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

    /**
     * Resolves the view from the request.
     */
    protected function resolveView(View $view, Request $request): View
    {
        return new View(
            component: $view->component,
            properties: $this->resolveProperties($view, $request),
        );
    }

    /**
     * Resolves the properties on the given view or dialog.
     */
    protected function resolveProperties(Dialog|View $view, Request $request): array
    {
        // We don't use dependency injection, because the request object
        // could be different than the one given to `toResponse`.
        $resolver = resolve(PropertiesResolver::class, ['request' => $request]);

        return $resolver->resolve(
            component: $view->component,
            properties: [...$this->hybridly->shared(), ...$view->properties],
            persisted: $this->hybridly->persisted(),
        );
    }

    /**
     * Resolves the URL that will be shown in the browser.
     */
    protected function resolveUrl(Request $request): string
    {
        if ($resolver = $this->hybridly->getUrlResolver()) {
            return app()->call($resolver, [
                'request' => $request,
            ]);
        }

        return $request->fullUrl();
    }
}
