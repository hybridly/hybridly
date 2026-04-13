<?php

namespace Hybridly\Configuration;

use Hybridly\Architecture\ResourcesComponentLoader;

/**
 * @see https://hybridly.dev/guide/architecture.html
 */
final class Architecture
{
    public const ENTRYPOINT = 'main.ts';
    public const ROOT_VIEW = 'root';

    public string $applicationMainPath {
        get => $this->rootDirectory . '/' . $this->entrypoint;
    }

    public function __construct(
        public readonly string $rootView,
        public readonly bool $eagerLoadViews,
        public readonly string $entrypoint,
        public readonly string $rootDirectory,
        public readonly string $componentLoader,
    ) {}

    public static function fromArray(array $config): static
    {
        return new static(
            rootView: $config['root_view'] ?? self::ROOT_VIEW,
            eagerLoadViews: $config['eager_load_views'] ?? true,
            entrypoint: $config['entrypoint'] ?? self::ENTRYPOINT,
            rootDirectory: $config['root_directory'] ?? 'resources',
            componentLoader: $config['component_loader'] ?? ResourcesComponentLoader::class,
        );
    }
}
