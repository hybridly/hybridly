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
            return 'unknown';
        }
    }

    public static function isLatestVersion(): bool
    {
        return version_compare(
            version1: InstalledVersions::getVersion('hybridly/laravel'),
            version2: static::getLatestVersion(),
            operator: '<=',
        );
    }

    public static function getPrettyComposerVersion(): string
    {
        return static::formatVersion(InstalledVersions::getPrettyVersion('hybridly/laravel'));
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
        ['version' => $version] = rescue(
            callback: fn () => File::json(base_path('node_modules/hybridly/package.json')),
            rescue: ['version' => null],
            report: false,
        );

        return static::formatVersion($version);
    }

    private static function formatVersion(?string $version): string
    {
        if (!$version) {
            return '<fg=red;options=bold>NOT INSTALLED</>';
        }

        $version = str($version)->start('v');

        if (static::isLatestVersion()) {
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
