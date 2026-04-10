<?php

namespace App\KitchenSink\Forms\Validation;

enum Species: string
{
    case HUMAN = 'human';
    case ELF = 'elf';
    case DWARF = 'dwarf';

    public function toLabel(): string
    {
        return match ($this) {
            self::HUMAN => 'Human',
            self::ELF => 'Elf',
            self::DWARF => 'Dwarf',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->toLabel()])
            ->all();
    }
}
