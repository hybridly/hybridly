<?php

namespace Hybridly\Support;

use Illuminate\Support\Arr as SupportArr;

class Arr extends SupportArr
{
    /**
     * Get a subset of the items from the given array, with dot notation support.
     */
    public static function onlyDot(array $array, string|array $only): array
    {
        return self::only($array, $only) +
            collect(self::dot($array))
                ->filter(fn ($_, $key) => collect($only)->some(fn ($only) => $only === $key || str_starts_with($key, $only . '.')))
                ->undot()
                ->toArray();
    }

    /**
     * Get all of the given array except for a specified array of keys, with dot notation support.
     */
    public static function exceptDot(array $array, string|array $except): array
    {
        return self::except($array, $except);
    }
}
