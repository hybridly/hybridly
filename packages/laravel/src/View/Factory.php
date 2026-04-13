<?php

namespace Hybridly\View;

use Hybridly\Contracts\HybridResponse;
use Hybridly\Exceptions\MissingViewComponentException;
use Hybridly\Hybridly;
use Hybridly\Support\Arr as SupportArr;
use Hybridly\Support\Configuration\Configuration;
use Hybridly\Support\Header;
use Hybridly\Support\Pagination\ScrollMetadata;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Router;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class Factory implements HybridResponse
{
    protected ?View $view = null;
    protected ?View $dialogView = null;
    protected ?string $dialogBaseUrl = null;
    protected bool $redirectToDialogBase = false;
    protected bool $preserveBaseOnClose = false;

    public function __construct(
        protected Hybridly $hybridly,
        protected Router $router,
        protected DialogResolver $dialogResolver,
        protected ResponseFactory $responseFactory,
        protected Configuration $configuration,
    ) {}

    /**
     * Configures the dialog.
     *
     * Setting `redirectToBase` to `true` will always force a redirect to the base view when rendering the dialog instead of opening it in the current page.
     * Setting `preserveCurrentBase` to `true` will prevent returning an updated base view when rendering the dialog from.
     */
    public function configureDialog(string $baseUrl, bool $alwaysRedirectToBase = false, bool $preserveBaseOnClose = false): static
    {
        $this->dialogBaseUrl = $baseUrl;

        if ($alwaysRedirectToBase) {
            $this->redirectToDialogBase = true;
        }

        if ($preserveBaseOnClose) {
            $this->preserveBaseOnClose = true;
        }

        return $this;
    }

    /**
     * Sets the hybridly view data.
     */
    public function withView(string $component, iterable $properties = []): static
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
    public function withComponent(string $component): static
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
    public function withProperties(iterable $properties): static
    {
        $this->view = new View(
            component: $this->view?->component,
            properties: $this->transformProperties($properties),
        );

        return $this;
    }

    /**
     * Adds a property to the view.
     */
    public function withProperty(string $key, mixed $value = null): static
    {
        $this->view->properties[$key] = $value;

        return $this;
    }

    /**
     * Renders the response content.
     */
    public function render(): string|false
    {
        return $this->toResponse(request())->getContent();
    }

    /**
     * Generates a response for the given request.
     *
     * @return \Illuminate\Http\Response
     */
    public function toResponse($request)
    {
        $payload = new Payload(
            view: $this->resolveView($this->view, $request),
            dialog: $this->resolveDialog($request),
            url: $this->resolveUrl($request),
            version: $this->hybridly->version,
            validation: $this->resolveValidation($request),
        );

        if ($payload->dialog) {
            $payload = $this->renderDialog($request, $payload);
        }

        // If the component is missing and there is no page loaded,
        // throw an exception because the front-end cannot handle that situation.
        if (! $this->hybridly->isHybrid($request) && ! $this->view->component) {
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
            view: $this->configuration->architecture->rootView,
            data: ['payload' => $payload->toArray()],
        );
    }

    private function renderDialog(Request $request, Payload $payload): Payload
    {
        // Dialogs do not need shared properties, as they are already part of the base view.
        // See: https://github.com/hybridly/hybridly/pull/153
        [$properties, $deferred, $mergeable, $paginators] = $this->resolveProperties(
            view: $payload->dialog,
            request: $request,
            includeSharedProperties: false,
        );

        return new Payload(
            // For performance reason, we may omit computing the base view.
            // This is useful when that view already exists, but
            // only works when coming from an HTML request.
            view: $this->preserveBaseOnClose && $this->hybridly->isHybrid($request)
                ? null
                : $this->resolveBaseView(
                    targetUrl: $this->redirectToDialogBase
                        ? $payload->dialog->baseUrl
                        : $payload->dialog->redirectUrl,
                    originalRequest: $request,
                ),
            url: $payload->url,
            version: $payload->version,
            validation: $payload->validation,
            dialog: new Dialog(
                component: $payload->dialog->component,
                properties: $properties,
                baseUrl: $payload->dialog->baseUrl,
                redirectUrl: $this->redirectToDialogBase
                    ? $payload->dialog->baseUrl
                    : $payload->dialog->redirectUrl,
                key: $payload->dialog->key,
                deferred: $deferred,
                mergeable: $mergeable,
                paginators: $paginators,
            ),
        );
    }

    /**
     * Gets the base view for the given URL.
     */
    private function resolveBaseView(string $targetUrl, Request $originalRequest): View
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

        if ($originalRequest->hasSession() && ($session = $originalRequest->session())) {
            $request->setLaravelSession($session);
        }

        App::instance('request', $request);

        $response = (new SubstituteBindings($this->router))->handle(
            request: $request,
            next: fn () => $route->run(),
        );

        if ($response instanceof RedirectResponse) {
            return $this->resolveBaseView($response->getTargetUrl(), $request);
        }

        if (! ($response instanceof self)) {
            throw new \LogicException(\sprintf('Target URL [%s] does not return a hybrid response.', $targetUrl));
        }

        return $this->resolveView($response->view, $request);
    }

    /**
     * Resolves the dialog from the request.
     */
    private function resolveDialog(Request $request): ?Dialog
    {
        if (! $this->dialogBaseUrl) {
            return null;
        }

        return $this->dialogResolver->resolve(
            baseUrl: $this->dialogBaseUrl,
            request: $request,
            view: new View(
                component: $this->view->component,
                properties: Arr::except($this->view->properties, array_keys($this->hybridly->sharedProperties)),
                deferred: [],
                mergeable: [],
            ),
        );
    }

    /**
     * Resolves the view from the request.
     */
    private function resolveView(View $view, Request $request): View
    {
        [$properties, $deferred, $mergeable, $paginators] = $this->resolveProperties($view, $request);

        return new View(
            component: $view->component,
            properties: $properties,
            deferred: $deferred,
            mergeable: $mergeable,
            paginators: $paginators,
        );
    }

    /**
     * Resolves the properties on the given view or dialog.
     */
    private function resolveProperties(Dialog|View $view, Request $request, bool $includeSharedProperties = true): array
    {
        // We don't use dependency injection, because the request object
        // could be different than the one given to `toResponse`.
        $resolver = resolve(PropertiesResolver::class, ['request' => $request]);

        [$properties, $deferred, $mergeable, $paginators] = $resolver->resolve(
            component: $view->component,
            properties: $includeSharedProperties ? [...$this->hybridly->sharedProperties, ...$view->properties] : $view->properties,
            persistedByPath: $this->hybridly->persistedProperties,
        );

        return [
            $properties,
            $deferred,
            $mergeable,
            array_map(static fn (ScrollMetadata $metadata) => $metadata->toArray(), $paginators),
        ];
    }

    /**
     * Resolves the URL that will be shown in the browser.
     */
    private function resolveUrl(Request $request): string
    {
        if ($resolver = $this->hybridly->getUrlResolver()) {
            return App::call($resolver, [
                'request' => $request,
            ]);
        }

        return $request->fullUrl();
    }

    /**
     * Resolves validation errors grouped by bag name.
     */
    private function resolveValidation(Request $request): array
    {
        if (! $request->hasSession()) {
            return [];
        }

        if (! ($errors = $request->session()->get('errors'))) {
            return [];
        }

        if (! ($errors instanceof ViewErrorBag)) {
            return [];
        }

        $resolved = collect($errors->getBags())
            ->map(fn (MessageBag $bag) => array_map(fn (array $messages) => $messages[0], $bag->messages()))
            ->toArray();

        if (! ($error_bag = $request->header(Header::ERROR_BAG))) {
            return $resolved;
        }

        if (! array_key_exists('default', $resolved)) {
            return $resolved;
        }

        if (array_key_exists($error_bag, $resolved)) {
            return $resolved;
        }

        $resolved[$error_bag] = $resolved['default'];
        unset($resolved['default']);

        return $resolved;
    }

    private function transformProperties(iterable $properties): array
    {
        return SupportArr::resolveArrayableProperties($properties);
    }
}
