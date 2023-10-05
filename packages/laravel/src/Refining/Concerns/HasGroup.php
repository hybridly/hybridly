<?php

namespace Hybridly\Refining\Concerns;

trait HasGroup
{
    protected static array $groupRefiners = [];
    protected static array $groupOptions = [];

    public static function setGroup(array $refiners, array $options = []): void
    {
        static::$groupRefiners = $refiners;
        static::$groupOptions = $options;
    }

    public static function getGroupOption(string $name, mixed $default = null): mixed
    {
        return static::$groupOptions[$name] ?? $default;
    }

    public static function hasGroup(): bool
    {
        return \count(static::$groupRefiners) > 0;
    }

    public static function getGroupOptions(): ?array
    {
        if (!static::hasGroup()) {
            return null;
        }

        return static::$groupOptions;
    }

    public static function clearGroup(): void
    {
        static::$groupRefiners = [];
        static::$groupOptions = [];
    }
}
