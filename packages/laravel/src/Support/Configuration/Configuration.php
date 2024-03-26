<?php

namespace Hybridly\Support\Configuration;

/** @internal */
final class Configuration
{
    public function __construct(
        public readonly Router $router,
        public readonly Architecture $architecture,
        public readonly Refining $refining,
        public readonly Tables $tables,
        public readonly Internationalization $internationalization,
        public readonly Properties $properties,
        public readonly Testing $testing,
        public readonly TypeScript $typescript,
    ) {
    }

    public static function fromArray(array $config): static
    {
        return new static(
            router: Router::fromArray($config['router'] ?? []),
            architecture: Architecture::fromArray($config['architecture'] ?? []),
            refining: Refining::fromArray($config['refining'] ?? []),
            tables: Tables::fromArray($config['tables'] ?? []),
            internationalization: Internationalization::fromArray($config['internationalization'] ?? []),
            properties: Properties::fromArray($config['properties'] ?? []),
            testing: Testing::fromArray($config['testing'] ?? []),
            typescript: TypeScript::fromArray($config['typescript'] ?? []),
        );
    }

    public static function get(): static
    {
        return resolve(self::class);
    }
}
