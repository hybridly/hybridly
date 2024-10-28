<?php

namespace Hybridly\Support;

use Hybridly\Support\Configuration\Configuration;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Reflector;
use JsonSerializable;
use ReflectionClass;
use ReflectionMethod;

class RouteExtractor implements JsonSerializable, Arrayable
{
    public function __construct(
        private readonly Router $router,
        private readonly Configuration $configuration,
    ) {
    }

    public function getRouteCollection(): array
    {
        $url = rtrim(url('/'), '/');

        return [
            'url' => $url,
            'port' => parse_url($url)['port'] ?? null,
            'defaults' => $this->getDefaultParameters(),
            'routes' => $this->getRoutes(),
        ];
    }

    /**
     * Gets an array of routes.
     */
    public function getRoutes(): array
    {
        $routes = collect($this->router->getRoutes())
            ->filter(function (Route $route) {
                if (!$route->getName()) {
                    return false;
                }

                if ($this->isVendorRoute($route)) {
                    return false;
                }

                if (str($route->uri())->is($this->configuration->router->excludedRoutes)) {
                    return false;
                }

                return true;
            })
            ->mapWithKeys(fn (Route $route) => [
                $route->getName() => [
                    'domain' => $route->domain(),
                    'method' => $route->methods(),
                    'uri' => rtrim(ltrim($route->uri(), '/'), '/'),
                    'name' => $route->getName(),
                    'bindings' => $this->resolveBindings($route),
                    'wheres' => $route->wheres,
                ],
            ])
            ->all();

        return $routes;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toJson();
    }

    public function toJson()
    {
        return json_encode($this->toArray());
    }

    public function toArray()
    {
        return $this->getRouteCollection();
    }

    /**
     * Resolves route model bindings.
     * @see https://github.com/tighten/ziggy/blob/main/src/Ziggy.php#L162
     */
    protected function resolveBindings(Route $route): array
    {
        $bindings = [];

        foreach ($route->signatureParameters(UrlRoutable::class) as $parameter) {
            if (!\in_array($parameter->getName(), $route->parameterNames(), true)) {
                break;
            }

            $model = Reflector::getParameterClassName($parameter);
            $override = (new ReflectionClass($model))->isInstantiable()
                && (new ReflectionMethod($model, 'getRouteKeyName'))->class !== Model::class;

            // Avoid booting this model if it doesn't override the default route key name
            $bindings[$parameter->getName()] = $override ? app($model)->getRouteKeyName() : 'id';
        }

        return method_exists($route ?: '', 'bindingFields')
            ? array_merge($bindings, $route->bindingFields())
            : $bindings;
    }

    /**
     * Determines if the route has been defined outside of the application.
     */
    protected function isVendorRoute(Route $route): bool
    {
        if ($route->action['uses'] instanceof \Closure) {
            $path = (new \ReflectionFunction($route->action['uses']))->getFileName();
        } elseif (\is_string($route->action['uses']) && str_contains($route->action['uses'], 'SerializableClosure')) {
            return false;
        } elseif (\is_string($route->action['uses'])) {
            if ($this->isFrameworkController($route)) {
                return false;
            }

            $path = (new ReflectionClass($route->getControllerClass()))->getFileName();
        } else {
            return false;
        }

        $allowedVendors = [
            ...$this->configuration->router->allowedVendors,
            'hybridly/laravel',
        ];

        foreach ($allowedVendors as $vendor) {
            if (str_starts_with($path, strtr(base_path('vendor/' . $vendor), '/\\', \DIRECTORY_SEPARATOR))) {
                return false;
            }
        }

        return str_starts_with($path, base_path('vendor'));
    }

    /**
     * Determines if the route uses a framework controller.
     */
    protected function isFrameworkController(Route $route): bool
    {
        return \in_array($route->getControllerClass(), [
            '\Illuminate\Routing\RedirectController',
            '\Illuminate\Routing\ViewController',
        ], true);
    }

    /**
     * Gets global default parameters.
     */
    protected function getDefaultParameters(): array
    {
        return method_exists(app('url'), 'getDefaultParameters')
            ? app('url')->getDefaultParameters()
            : [];
    }
}
