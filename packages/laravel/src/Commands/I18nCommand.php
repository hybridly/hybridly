<?php

namespace Hybridly\Commands;

use Hybridly\Support\Configuration\Configuration;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Generates a JSON file with all translations.
 */
class I18nCommand extends Command
{
    protected $signature = 'hybridly:i18n {--L|locales}';
    protected $description = 'Generates JSON translation files.';
    protected $hidden = true;

    public function handle(): int
    {
        return $this->writeTranslations()
            ? self::SUCCESS
            : self::FAILURE;
    }

    /**
     * Recursively loads translations in the given directory.
     */
    protected function makeFolderFilesTree(string $directory): array
    {
        $tree = [];

        if (!$files = @scandir($directory)) {
            return $tree;
        }

        foreach ($files as $fileName) {
            if (str_starts_with($fileName, '.')) {
                continue;
            }

            $extension = '.' . Str::afterLast($fileName, '.');
            $fileName = basename($fileName, $extension);
            $tree[$fileName] = [];

            if (is_dir($directory . '/' . $fileName)) {
                $tree[$fileName] = $this->makeFolderFilesTree($directory . '/' . $fileName);
            }

            if (is_file($pathName = $directory . '/' . $fileName . $extension)) {
                if ($extension === '.json') {
                    $existingTranslations = $tree[$fileName] ?? [];

                    $tree[$fileName] = array_merge(
                        $existingTranslations,
                        json_decode(file_get_contents($pathName), true),
                    );
                }

                if ($extension === '.php') {
                    $tree[$fileName] = require($pathName);
                }
            }
        }

        return $tree;
    }

    /**
     * Gets all defined locales for this application.
     */
    protected function getLocales(): array
    {
        if (!$files = @scandir($this->getLangPath())) {
            return [];
        }

        return collect($files)
            ->filter(fn ($file) => !\in_array($file, ['.', '..'], true))
            ->map(fn ($file) => str($file)->beforeLast('.')->toString())
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Gets the translations as an array.
     */
    protected function getTranslations(string $lang = null): array
    {
        $translations = $this->makeFolderFilesTree($this->getLangPath());

        if ($lang) {
            return $translations[$lang] ?? [];
        }

        return $translations;
    }

    /**
     * Gets the translations as an JSON-encoded string.
     */
    protected function getTranslationsAsJson(string $lang = null): string
    {
        return str(json_encode($this->getTranslations($lang)))
            ->replaceMatches('/:(\w+)/', '{${1}}')
            ->replaceMatches('/\@/', '{\'@\'}')
            ->toString();
    }

    /**
     * Gets the path to the locales directory.
     */
    protected function getLocalesPath(): string
    {
        return \dirname(str_replace('/', \DIRECTORY_SEPARATOR, Configuration::get()->internationalization->localesPath));
    }

    /**
     * Gets the path to the lang directory.
     */
    protected function getLangPath(): string
    {
        // Could be improved by loading both the
        // Laravel-provided langs and custom ones.
        $langPath = Configuration::get()->internationalization->langPath;

        if (!File::isDirectory($langPath)) {
            return base_path('vendor/laravel/framework/src/Illuminate/Translation/lang');
        }

        return $langPath;
    }

    /**
     * Gets the path for the given locale.
     */
    protected function getLocalePath(string $locale = null): string
    {
        return implode(\DIRECTORY_SEPARATOR, [
            $this->getLocalesPath(),
            $locale
                ? str_replace('{locale}', $locale, Configuration::get()->internationalization->fileNameTemplate)
                : Configuration::get()->internationalization->fileName,
        ]);
    }

    /**
     * Writes the translations to the file system.
     */
    protected function writeTranslations(): bool
    {
        File::ensureDirectoryExists($this->getLocalesPath());

        if ($this->option('locales')) {
            return $this->writeTranslationsInLocaleFiles();
        }

        return $this->writeTranslationsInSingleFile();
    }

    /**
     * Writes all translations in their locale file.
     */
    protected function writeTranslationsInLocaleFiles(): bool
    {
        foreach ($this->getLocales() as $locale) {
            File::ensureDirectoryExists(\dirname($path = $this->getLocalePath($locale)));

            if (!File::put($path, $this->getTranslationsAsJson($locale))) {
                return false;
            }

            $this->writeSuccess($path, $locale);
        }

        return true;
    }

    /**
     * Writes all translations in their own file.
     */
    protected function writeTranslationsInSingleFile(): bool
    {
        $result = (bool) File::put(
            $path = $this->getLocalePath(),
            $this->getTranslationsAsJson(),
        );

        if ($result) {
            $this->writeSuccess($path);
        }

        return $result;
    }

    protected function writeSuccess(string $path, string $locale = null): void
    {
        $this->components->info(
            \sprintf(
                '%s written to <comment>%s</comment>.',
                $locale ? "<comment>{$locale}</comment>" : 'Translations',
                $path,
            ),
        );
    }
}
