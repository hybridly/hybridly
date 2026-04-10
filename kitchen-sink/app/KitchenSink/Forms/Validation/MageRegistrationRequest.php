<?php

namespace App\KitchenSink\Forms\Validation;

use Illuminate\Validation\Rules\Enum;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
final class MageRegistrationRequest extends Data
{
    /**
     * @param list<string> $known_spells
     */
    public function __construct(
        public readonly string $full_name,
        public readonly string $date_of_birth,
        public readonly string $species,
        public readonly array $known_spells,
        public readonly bool $has_been_expelled,
        public readonly string $moral_alignment,
        public readonly ?string $comments,
    ) {}

    public static function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:3'],
            'date_of_birth' => ['required', 'date', 'before:today'],
            'species' => ['required', new Enum(Species::class)],
            'known_spells' => ['required', 'array', 'min:1', 'max:5'],
            'known_spells.*' => ['required', 'string', 'min:2'],
            'has_been_expelled' => ['required', 'boolean'],
            'moral_alignment' => ['required', new Enum(MoralAlignment::class)],
            'comments' => ['nullable', 'string'],
        ];
    }
}
