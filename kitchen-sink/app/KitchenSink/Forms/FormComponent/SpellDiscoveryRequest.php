<?php

namespace App\KitchenSink\Forms\FormComponent;

use Illuminate\Validation\Rules\Enum;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class SpellDiscoveryRequest extends Data
{
    public function __construct(
        public readonly string $mage_reference_id,
        public readonly string $spell_name,
        public readonly string $spell_risk_level,
        public readonly string $spell_explanation,
    ) {}

    public static function rules(): array
    {
        return [
            'mage_reference_id' => ['required', 'string'],
            'spell_name' => ['required', 'string', 'min:2'],
            'spell_risk_level' => ['required', new Enum(SpellRiskLevel::class)],
            'spell_explanation' => ['required', 'string', 'min:10', 'max:1000'],
        ];
    }
}
