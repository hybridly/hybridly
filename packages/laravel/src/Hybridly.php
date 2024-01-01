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
}
