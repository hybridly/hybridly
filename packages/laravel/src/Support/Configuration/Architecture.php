<?php

namespace Hybridly\Support\Configuration;

/**
 * @see https://hybridly.dev/guide/architecture.html
 */
final class Architecture
{
    public const APPLICATION_MAIN = 'main.ts';
    public const ROOT_VIEW = 'root';

    public function __construct(
        public readonly string $rootView,
        public readonly bool $loadDefaultModule,
        public readonly bool $eagerLoadViews,
        public readonly array $extensions,
        public readonly string $applicationDirectory,
        public readonly string $applicationMain,
        public readonly string $rootDirectory,
        public readonly string $viewsDirectory,
        public readonly string $layoutsDirectory,
        public readonly string $componentsDirectory,
        public readonly array $excludedViewsDirectories,
    ) {
    }

    public static function fromArray(array $config): static
    {
        return new static(
            rootView: $config['root_view'] ?? self::ROOT_VIEW,
            loadDefaultModule: $config['load_default_module'] ?? true,
            eagerLoadViews: $config['eager_load_views'] ?? true,
            extensions: $config['extensions'] ?? ['vue', 'tsx'],
            applicationDirectory: $config['application_directory'] ?? 'application',
            applicationMain: $config['application_main'] ?? self::APPLICATION_MAIN,
            rootDirectory: $config['root_directory'] ?? 'resources',
            viewsDirectory: $config['views_directory'] ?? 'views',
            layoutsDirectory: $config['layouts_directory'] ?? 'layouts',
            componentsDirectory: $config['components_directory'] ?? 'components',
            excludedViewsDirectories: $config['excluded_views_directories'] ?? [],
        );
    }

    public function getApplicationMainPath(): string
    {
        return $this->rootDirectory . '/' . $this->applicationDirectory . '/' . $this->applicationMain;
    }
}
