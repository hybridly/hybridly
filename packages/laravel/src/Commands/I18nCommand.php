<?php

namespace Hybridly\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Generates a JSON file with all translations.
 */
class I18nCommand extends Command
{
    protected $signature = 'hybridly:i18n {--L|locales} {--clean}';
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

        if (!is_dir($directory)) {
            return $tree;
        }

        if (!$files = scandir($directory)) {
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
        if (!$files = scandir(config('hybridly.i18n.lang_path', lang_path()))) {
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
        $translations = $this->makeFolderFilesTree(config('hybridly.i18n.lang_path'));

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
        return preg_replace('/:(\w+)/', '{${1}}', json_encode($this->getTranslations($lang)));
    }

    /**
     * Gets the path to the locales directory.
     */
    protected function getLocalesPath(): string
    {
        return \dirname(str_replace('/', \DIRECTORY_SEPARATOR, config('hybridly.i18n.locales_path', base_path('.hybridly/i18n.json'))));
    }

    /**
     * Gets the path for the given locale.
     */
    protected function getLocalePath(string $locale = null): string
    {
        return implode(\DIRECTORY_SEPARATOR, [
            $this->getLocalesPath(),
            $locale
                ? str_replace('{locale}', $locale, config('hybridly.i18n.file_name_template'))
                : config('hybridly.i18n.file_name', 'locales.json'),
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
        if ($this->option('clean')) {
            File::cleanDirectory(\dirname($this->getLocalePath()));
        }

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
            sprintf(
                "%s written to <comment>%s</comment>.",
                $locale ? "<comment>{$locale}</comment>" : 'Translations',
                $path,
            ),
        );
    }
}
