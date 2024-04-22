<?php

namespace Hybridly;

use Hybridly\Architecture\ComponentsResolver;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\ForwardsCalls;
use Illuminate\Support\Traits\Macroable;

/** @mixin ComponentsResolver */
final class Hybridly
{
    use Concerns\ForwardsToHybridlyHelpers;
    use Concerns\HasPersistentProperties;
    use Concerns\HasRootView;
    use Concerns\HasSharedProperties;
    use Concerns\HasVersion;
    use Concerns\ResolvesUrls;
    use Conditionable;
    use ForwardsCalls, Macroable {
        Macroable::__call as macroCall;
        forwardDecoratedCallTo as forward;
    }

    public function __construct(
        private readonly ComponentsResolver $components,
    ) {
    }

    public function __call($method, $parameters)
    {
        if (static::hasMacro($method)) {
            return $this->macroCall($method, $parameters);
        }

        return $this->forwardDecoratedCallTo($this->components, $method, $parameters);
    }

    /**
     * Loads a namespaced module and its views, layouts and components, in the current directory.
     *
     * @see https://hybridly.dev/api/laravel/hybridly.html#loadmodule
     */
    public function loadModule(
        null|string|array $namespace = null,
        bool $deep = true,
        bool $loadViews = true,
        bool $loadLayouts = true,
        bool $loadComponents = true,
        bool $loadTypeScript = true,
    ): static {
        $trace = debug_backtrace(
            options: \DEBUG_BACKTRACE_IGNORE_ARGS,
            limit: 1,
        );

        $this->loadModuleFrom(
            directory: \dirname($trace[0]['file']),
            namespace: $namespace,
            deep: $deep,
            loadViews: $loadViews,
            loadLayouts: $loadLayouts,
            loadComponents: $loadComponents,
            loadTypeScript: $loadTypeScript,
        );

        return $this;
    }
}
