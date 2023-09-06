<?php

namespace Hybridly;

use Hybridly\Support\Header;
use Hybridly\Support\Partial;
use Hybridly\Support\VueViewFinder;
use Hybridly\View\Factory;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;
use Spatie\LaravelData\Contracts\DataObject;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

final class Hybridly
{
    use Concerns\HasPersistentProperties;
    use Concerns\HasRootView;
    use Concerns\HasSharedProperties;
    use Concerns\HasVersion;
    use Concerns\HasViewFinder;
    use Concerns\ResolvesUrls;
    use Conditionable;
    use Macroable;

    public const DEFAULT_ROOT_VIEW = 'root';

    public function __construct(
        private readonly VueViewFinder $finder,
    ) {
    }

    /**
     * Returns a hybrid view.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#view
     */
    public function view(string $component = null, array|Arrayable|DataObject $properties = []): Factory
    {
        return resolve(Factory::class)->view($component, $properties);
    }

    /**
     * Generates a response for redirecting to an external website, or a non-hybrid page.
     * This can also be used to redirect to a hybrid page when it is not known whether the current request is hybrid or not.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#external
     */
    public function external(string|RedirectResponse $url): Response
    {
        if ($url instanceof RedirectResponse) {
            $url = $url->getTargetUrl();
        }

        if ($this->isHybrid()) {
            return new Response(
                status: Response::HTTP_CONFLICT,
                headers: [Header::EXTERNAL => $url],
            );
        }

        return new RedirectResponse($url);
    }

    /**
     * Creates a property that will only get evaluated and included when specifically requested through a partial reload.
     * Partial properties are not included during the first load.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#partial
     */
    public function partial(\Closure $callback): Partial
    {
        return new Partial($callback);
    }

    /**
     * Determines whether the current request is hybrid.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#ishybrid
     */
    public function isHybrid(Request $request = null): bool
    {
        $request ??= request();

        return $request->headers->has(Header::HYBRID_REQUEST);
    }

    /**
     * Determines whether the current request is a partial reload.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#ispartial
     */
    public function isPartial(Request $request = null): bool
    {
        $request ??= request();

        if (!$this->isHybrid($request)) {
            return false;
        }

        return $request->headers->has(Header::PARTIAL_COMPONENT);
    }

    /**
     * Flashes data to the session.
     */
    public function flash(array|string $key, mixed $value = null): static
    {
        $key = \is_array($key) ? $key : [$key => $value];

        foreach ($key as $k => $v) {
            session()->flash($k, $v);
        }

        return $this;
    }
}
