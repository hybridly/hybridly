<?php

namespace Monolikit\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Generates a JSON file with all translations.
 */
class GenerateI18nCommand extends Command
{
    protected $signature = 'monolikit:i18n';
    protected $description = 'Generates JSON translation files.';
    protected $hidden = true;

    public function handle(): int
    {
        if ($success = $this->writeTranslations()) {
            $this->components->info("Translations written to <comment>{$this->getTranslationFilePath()}</comment>.");
        }

        return $success
            ? self::SUCCESS
            : self::FAILURE;
    }

    /**
     * Recursively loads translations in the given directory.
     */
    protected function makeFolderFilesTree(string $directory): array
    {
        $tree = [];
        if (!$files = scandir($directory)) {
            return [];
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
     * Gets the translations as an array.
     */
    protected function getTranslations(): array
    {
        return $this->makeFolderFilesTree(resource_path('lang'));
    }

    /**
     * Gets the path to the translation file.
     */
    protected function getTranslationFilePath(): string
    {
        return str_replace('/', \DIRECTORY_SEPARATOR, config('monolikit.i18n_path'));
    }

    /**
     * Writes the translations to the file system.
     */
    protected function writeTranslations(): bool
    {
        return (bool) File::put(
            $this->getTranslationFilePath(),
            preg_replace('/:(\w+)/', '{${1}}', json_encode($this->getTranslations())),
        );
    }
}
