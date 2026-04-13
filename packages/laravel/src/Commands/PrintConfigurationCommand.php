<?php

namespace Hybridly\Commands;

use Hybridly\Architecture\ComponentRepository;
use Hybridly\Architecture\ComponentType;
use Hybridly\Configuration\Configuration;
use Hybridly\Support\Version;
use Illuminate\Console\Command;

final class PrintConfigurationCommand extends Command
{
    protected $signature = 'hybridly:config {--pretty=false}';
    protected $description = 'Prints the internal Hybridly configuration.';
    protected $hidden = true;

    public function handle(Configuration $config, ComponentRepository $components): int
    {
        $routeExtractor = $this->laravel->make($config->router->routesExtractor);

        $configuration = [
            'versions' => [
                'composer' => Version::getComposerVersion(),
                'npm' => Version::getNpmVersion(),
                'is_latest' => Version::isLatestVersion(),
                'latest' => Version::getLatestVersion(),
            ],
            'architecture' => [
                'root_directory' => $config->architecture->rootDirectory,
                'application_main_path' => $config->architecture->applicationMainPath,
            ],
            'components' => [
                'eager' => $config->architecture->eagerLoadViews,
                'layouts' => $components->list(ComponentType::LAYOUT),
                'views' => $components->list(ComponentType::VIEW),
            ],
            'routing' => [
                ...$routeExtractor->toArray(),
                'absolute' => $config->router->generateAbsoluteUrls,
            ],
        ];

        // We do a lil bit of h4cking around the `pretty` option
        // to affect what is returned in the configuration
        if ($pretty = ($only = $this->option('pretty')) !== 'false') {
            if (! \in_array($only, ['true', 'false', null], strict: true)) {
                $configuration = data_get($configuration, $only);
            }

            $pretty = true;
        }

        $this->output->write(json_encode(
            value: $configuration,
            flags: $pretty ? (\JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES) : 0,
        ));

        return self::SUCCESS;
    }
}
