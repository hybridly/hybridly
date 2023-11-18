<?php

namespace Hybridly\Refining\Concerns;

trait HasGroup
{
    protected static array $groupRefiners = [];
    protected static array $groupOptions = [];

    /**
     * @var bool $multiSorting Determines if there is sorting of multiple columns.
     */
    protected bool $multiSorting = false;

    /**
     * Sets the group as having multiple sorters.
     *
     * @return void
     */
    public function setMultiSorting(): void
    {
        $this->multiSorting = true;
    }

    /**
     * Checks if the group has multiple sorters.
     *
     * @return bool Is multi sorting?
     */
    public function isMultiSorting(): bool
    {
        return $this->multiSorting;
    }

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
