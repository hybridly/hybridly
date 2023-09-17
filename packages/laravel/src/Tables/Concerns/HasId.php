<?php

namespace Hybridly\Tables\Concerns;

trait HasId
{
    private static null|\Closure $encodesIdUsing = null;
    private static null|\Closure $decodesIdUsing = null;

    public static function encodeIdUsing(null|\Closure $callback): void
    {
        static::$encodesIdUsing = $callback;
    }

    public static function decodeIdUsing(null|\Closure $callback): void
    {
        static::$decodesIdUsing = $callback;
    }

    public static function encodeId(string $id): string
    {
        if (self::$encodesIdUsing) {
            return value(self::$encodesIdUsing, $id);
        }

        return encrypt($id);
    }

    public static function decodeId(string $id): string
    {
        if (self::$decodesIdUsing) {
            return value(self::$decodesIdUsing, $id);
        }

        return decrypt($id);
    }

    public function getId(): string
    {
        return static::class;
    }
}
