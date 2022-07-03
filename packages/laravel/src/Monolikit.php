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

    public const MONOLIKIT_HEADER = 'x-monolikit';
    public const EXTERNAL_HEADER = 'x-monolikit-external';
    public const PARTIAL_COMPONENT_HEADER = 'x-monolikit-partial-component';
    public const ONLY_DATA_HEADER = 'x-monolikit-only-data';
    public const EXCEPT_DATA_HEADER = 'x-monolikit-except-data';
    public const CONTEXT_HEADER = 'x-monolikit-context';
    public const ERROR_BAG_HEADER = 'x-monolikit-error-bag';
    public const VERSION_HEADER = 'x-monolikit-version';
    public const DEFAULT_ROOT_VIEW = 'root';

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
     * Returns a lazy property that will get evaluated only when specifically required.
     */
    public function lazy(\Closure $callback): Lazy
    {
        return new Lazy($callback);
    }

    /**
     * Checks if the request is monolikit.
     */
    public function isMonolikit(Request $request = null): bool
    {
        $request ??= request();

        return $request->headers->has(self::MONOLIKIT_HEADER);
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
