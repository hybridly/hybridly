<?php

namespace App\KitchenSink\Forms\Validation;

enum SpellRiskLevel: string
{
    case LOW = 'low';
    case MODERATE = 'moderate';
    case HIGH = 'high';
    case REALITY_ALTERING = 'reality-altering';

    public function toLabel(): string
    {
        return match ($this) {
            self::LOW => 'Low',
            self::MODERATE => 'Moderate',
            self::HIGH => 'High',
            self::REALITY_ALTERING => 'Reality-altering',
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
