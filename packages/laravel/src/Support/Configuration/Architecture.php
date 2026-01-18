<?php

namespace Hybridly\Support\Configuration;

/**
 * @see https://hybridly.dev/guide/architecture.html
 */
final class Architecture
{
    public const APPLICATION_MAIN = 'application/main.ts';
    public const ROOT_VIEW = 'root';

    public string $applicationMainPath {
        get => $this->rootDirectory . '/' . $this->applicationMain;
    }

    public function __construct(
        public readonly string $rootView,
        public readonly bool $loadDefaultModule,
        public readonly bool $eagerLoadViews,
        public readonly array $extensions,
        public readonly string $applicationMain,
        public readonly string $rootDirectory,
    ) {}

    public static function fromArray(array $config): static
    {
        return new static(
            rootView: $config['root_view'] ?? self::ROOT_VIEW,
            loadDefaultModule: $config['load_default_module'] ?? true,
            eagerLoadViews: $config['eager_load_views'] ?? true,
            extensions: $config['extensions'] ?? ['vue', 'tsx'],
            applicationMain: $config['application_main'] ?? self::APPLICATION_MAIN,
            rootDirectory: $config['root_directory'] ?? 'resources',
        );
    }
}
