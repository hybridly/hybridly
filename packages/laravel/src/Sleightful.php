<?php

namespace Sleightful;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\Macroable;
use Sleightful\Concerns\HasRootView;
use Sleightful\Concerns\HasSharedProperties;
use Sleightful\Concerns\HasVersion;
use Sleightful\View\Factory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class Sleightful
{
    use Macroable;
    use HasRootView;
    use HasSharedProperties;
    use HasVersion;

    const SLEIGHTFUL_HEADER = 'x-sleightful';
    const EXTERNAL_HEADER = 'x-sleightful-external';
    const PARTIAL_COMPONENT_HEADER = 'x-sleightful-partial-component';
    const ONLY_DATA_HEADER = 'x-sleightful-only-data';
    const EXCEPT_DATA_HEADER = 'x-sleightful-except-data';
    const CONTEXT_HEADER = 'x-sleightful-context';
    const ERROR_BAG_HEADER = 'x-sleightful-error-bag';
    const VERSION_HEADER = 'x-sleightful-version';
    const DEFAULT_ROOT_VIEW = 'root';

    /**
     * Returns a sleightful view.
     */
    public function view(string $component, array|Arrayable $properties = []): Factory
    {
        if ($properties instanceof Arrayable) {
            $properties = $properties->toArray();
        }

        return resolve(Factory::class)->view($component, $properties);
    }

    /**
     * Redirects to the given URL using a full page load.
     */
    public function external(string|RedirectResponse $url): Response
    {
        if ($url instanceof RedirectResponse) {
            $url = $url->getTargetUrl();
        }

        if ($this->isSleightful()) {
            return new Response(
                status: Response::HTTP_CONFLICT,
                headers: [self::EXTERNAL_HEADER => $url],
            );
        }

        return new RedirectResponse($url);
    }

    /**
     * Checks if the request is sleightful.
     */
    public function isSleightful(Request $request = null): bool
    {
        $request ??= request();

        return $request->headers->has(self::SLEIGHTFUL_HEADER);
    }
}
