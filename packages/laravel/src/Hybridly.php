<?php

namespace Hybridly;

use Hybridly\Support\VueViewFinder;
use Illuminate\Support\Traits\Conditionable;
use Illuminate\Support\Traits\Macroable;

final class Hybridly
{
    use Concerns\ForwardsToHybridlyHelpers;
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
     * Flashes data to the session.
     * @deprecated
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
