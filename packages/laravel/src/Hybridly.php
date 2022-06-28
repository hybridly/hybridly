<?php

namespace Hybridly;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\Macroable;
use Hybridly\Concerns\HasRootView;
use Hybridly\Concerns\HasSharedProperties;
use Hybridly\Concerns\HasVersion;
use Hybridly\View\Factory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class Hybridly
{
    use Macroable;
    use HasRootView;
    use HasSharedProperties;
    use HasVersion;

    const HYBRIDLY_HEADER = 'x-hybridly';
    const EXTERNAL_HEADER = 'x-hybridly-external';
    const PARTIAL_COMPONENT_HEADER = 'x-hybridly-partial-component';
    const ONLY_DATA_HEADER = 'x-hybridly-only-data';
    const EXCEPT_DATA_HEADER = 'x-hybridly-except-data';
    const CONTEXT_HEADER = 'x-hybridly-context';
    const ERROR_BAG_HEADER = 'x-hybridly-error-bag';
    const VERSION_HEADER = 'x-hybridly-version';
    const DEFAULT_ROOT_VIEW = 'root';

    /**
     * Returns a hybridly view.
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

        if ($this->isHybridly()) {
            return new Response(
                status: Response::HTTP_CONFLICT,
                headers: [self::EXTERNAL_HEADER => $url],
            );
        }

        return new RedirectResponse($url);
    }

    /**
     * Checks if the request is hybridly.
     */
    public function isHybridly(Request $request = null): bool
    {
        $request ??= request();

        return $request->headers->has(self::HYBRIDLY_HEADER);
    }
}
