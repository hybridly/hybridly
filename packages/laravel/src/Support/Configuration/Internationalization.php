<?php

namespace Hybridly\Support\Configuration;

final class Internationalization
{
    public function __construct(
        public readonly string $fileNameTemplate,
        public readonly string $fileName,
        public readonly string $localesPath,
        public readonly string $langPath,
    ) {
    }

    public static function fromArray(array $config): static
    {
        return new static(
            fileNameTemplate: $config['file_name_template'] ?? '{locale}.json',
            fileName: $config['file_name'] ?? 'locales.json',
            localesPath: $config['locales_path'] ?? base_path('.hybridly/i18n.json'),
            langPath: $config['lang_path'] ?? lang_path(),
        );
    }
}
