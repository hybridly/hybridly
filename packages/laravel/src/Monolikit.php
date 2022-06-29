<?php

namespace Monolikit;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Support\Traits\Macroable;
use Monolikit\View\Factory;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class Monolikit
{
    use Macroable;
    use Concerns\HasRootView;
    use Concerns\HasSharedProperties;
    use Concerns\HasPersistentProperties;
    use Concerns\HasVersion;

    const MONOLIKIT_HEADER = 'x-monolikit';
    const EXTERNAL_HEADER = 'x-monolikit-external';
    const PARTIAL_COMPONENT_HEADER = 'x-monolikit-partial-component';
    const ONLY_DATA_HEADER = 'x-monolikit-only-data';
    const EXCEPT_DATA_HEADER = 'x-monolikit-except-data';
    const CONTEXT_HEADER = 'x-monolikit-context';
    const ERROR_BAG_HEADER = 'x-monolikit-error-bag';
    const VERSION_HEADER = 'x-monolikit-version';
    const DEFAULT_ROOT_VIEW = 'root';

    /**
     * Returns a monolikit view.
     */
    public function view(string $component, array|Arrayable $properties = []): Factory
    {
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

        if ($this->isMonolikit()) {
            return new Response(
                status: Response::HTTP_CONFLICT,
                headers: [self::EXTERNAL_HEADER => $url],
            );
        }

        return new RedirectResponse($url);
    }

    /**
     * Checks if the request is monolikit.
     */
    public function isMonolikit(Request $request = null): bool
    {
        $request ??= request();

        return $request->headers->has(self::MONOLIKIT_HEADER);
    }
}
