<?php

namespace Hybridly\Commands;

use Hybridly\Hybridly;
use Hybridly\RouteExtractor\RouteExtractor;
use Illuminate\Console\Command;

class PrintConfigurationCommand extends Command
{
    protected $name = 'hybridly:config';
    protected $description = 'Prints the internal Hybridly configuration.';
    protected $hidden = true;

    public function __construct(
        private readonly Hybridly $hybridly,
        private readonly RouteExtractor $routeExtractor,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        echo json_encode([
            'architecture' => [
                'root' => config('hybridly.architecture.root', 'resources'),
            ],
            'components' => [
                'eager' => (bool) config('hybridly.architecture.eager_load_views', true),
                'directories' => $this->hybridly->getViewFinder()->getLoadedDirectories(),
                'layouts' => $this->hybridly->getViewFinder()->getLayouts(),
                'views' => $this->hybridly->getViewFinder()->getViews(),
                'components' => $this->hybridly->getViewFinder()->getComponents(),
            ],
            'routing' => $this->routeExtractor->toArray(),
        ]);

        return self::SUCCESS;
    }
}
