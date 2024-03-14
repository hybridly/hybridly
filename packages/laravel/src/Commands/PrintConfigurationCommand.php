<?php

namespace Hybridly\Commands;

use Hybridly\Hybridly;
use Hybridly\Support\Configuration\Configuration;
use Hybridly\Support\RouteExtractor;
use Hybridly\Support\Version;
use Illuminate\Console\Command;

class PrintConfigurationCommand extends Command
{
    protected $signature = 'hybridly:config {--pretty} {buildPath?}';
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
        $buildPath = $this->argument('buildPath');
        if (!empty($buildPath)) {
            $this->hybridly->getViewFinder()->setBasePath(str_starts_with($buildPath, \DIRECTORY_SEPARATOR) ? $buildPath : base_path($buildPath));
        }
        $configuration = [
            'versions' => [
                'composer' => Version::getComposerVersion(),
                'npm' => Version::getNpmVersion(),
                'is_latest' => Version::isLatestVersion(),
                'latest' => Version::getLatestVersion(),
            ],
            'architecture' => [
                'root_directory' => Configuration::get()->architecture->rootDirectory,
                'components_directory' => Configuration::get()->architecture->componentsDirectory,
                'application_main_path' => Configuration::get()->architecture->getApplicationMainPath(),
            ],
            'components' => [
                'eager' => Configuration::get()->architecture->eagerLoadViews,
                'layouts' => $this->hybridly->getViewFinder()->getLayouts(),
                'views' => $this->hybridly->getViewFinder()->getViews(),
                'components' => $this->hybridly->getViewFinder()->getComponents(),
                'files' => $this->hybridly->getViewFinder()->getTypeScriptDirectories(),
            ],
            'routing' => $this->routeExtractor->toArray(),
        ];

        $flags = $this->option('pretty')
            ? \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES
            : 0;

        echo json_encode($configuration, $flags);

        return self::SUCCESS;
    }
}
