<?php

namespace App\KitchenSink\Forms\Validation;

enum MoralAlignment: string
{
    case LAWFUL = 'lawful';
    case NEUTRAL = 'neutral';
    case CHAOTIC = 'chaotic';

    public function toLabel(): string
    {
        return match ($this) {
            self::LAWFUL => 'Lawful',
            self::NEUTRAL => 'Neutral',
            self::CHAOTIC => 'Chaotic',
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
