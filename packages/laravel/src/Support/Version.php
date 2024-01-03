<?php

namespace Hybridly\Support;

use Composer\InstalledVersions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

final class Version
{
    private static ?string $latestVersion = null;

    public static function getLatestVersion(): string
    {
        if (static::$latestVersion) {
            return static::$latestVersion;
        }

        try {
            $response = Http::get('https://api.github.com/repos/hybridly/hybridly/releases/latest');

            return static::$latestVersion = $response['tag_name'];
        } catch (\Throwable) {
            return static::getComposerVersion();
        }
    }

    public static function isLatestVersion(?string $version = null): bool
    {
        return version_compare(
            version1: $version ?? self::getComposerVersion(),
            version2: str(static::getLatestVersion())->after('v'),
            operator: '==',
        );
    }

    public static function getComposerVersion(): string
    {
        return str(InstalledVersions::getPrettyVersion('hybridly/laravel'))->when->startsWith('v')->after('v');
    }

    public static function getPrettyComposerVersion(): string
    {
        return static::formatVersion(static::getComposerVersion());
    }

    public static function getNpmVersion(): ?string
    {
        try {
            ['version' => $version] = File::json(base_path('node_modules/hybridly/package.json'));

            return $version;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function getPrettyNpmVersion(): string
    {
        return static::formatVersion(static::getNpmVersion());
    }

    private static function formatVersion(?string $version): string
    {
        if (!$version) {
            return '<fg=red;options=bold>NOT INSTALLED</>';
        }

        $version = str($version)->start('v');

        if (static::isLatestVersion($version->after('v'))) {
            return $version;
        }

        $latest = static::getLatestVersion();

        return sprintf(
            '%s (%s available)',
            "<fg=red>{$version}</>",
            "<fg=green;options=bold>{$latest}</>",
        );
    }
}
