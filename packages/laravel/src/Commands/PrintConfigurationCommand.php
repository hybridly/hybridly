<?php

namespace Hybridly\Commands;

use Hybridly\Hybridly;
use Hybridly\Support\Configuration\Configuration;
use Hybridly\Support\Version;
use Illuminate\Console\Command;

class PrintConfigurationCommand extends Command
{
    protected $signature = 'hybridly:config {--pretty=false}';
    protected $description = 'Prints the internal Hybridly configuration.';
    protected $hidden = true;

    public function handle(Hybridly $hybridly): int
    {
        $routeExtractor = $this->laravel->make(Configuration::get()->router->routesExtractor);

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
                'layouts' => $hybridly->getLayouts(),
                'views' => $hybridly->getViews(),
                'components' => $hybridly->getComponents(),
                'files' => $hybridly->getTypeScriptDirectories(),
            ],
            'routing' => $routeExtractor->toArray(),
        ];

        // We do a lil bit of h4cking around the `pretty` option
        // to affect what is returned in the configuration
        if ($pretty = ($only = $this->option('pretty')) !== 'false') {
            if (!\in_array($only, ['true', 'false', null], strict: true)) {
                $configuration = data_get($configuration, $only);
            }

            $pretty = true;
        }

        $this->output->write(json_encode(
            value: $configuration,
            flags: $pretty ? \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES : 0,
        ));

        return self::SUCCESS;
    }
}
