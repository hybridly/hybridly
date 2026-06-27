<?php

namespace App\KitchenSink\DataLoading\Optimistic;

use Illuminate\Contracts\Cache\Repository as Cache;

final readonly class CharacterRepository
{
    private const CACHE_KEY = 'kitchen_sink_optimistic_characters';

    public function __construct(
        private Cache $cache,
    ) {}

    public function all(): array
    {
        return $this->cache->rememberForever(
            self::CACHE_KEY,
            fn () => $this->defaults(),
        );
    }

    public function setLiked(int $id, bool $liked): array
    {
        $characters = array_map(
            fn (Character $character) => $character->id === $id
                ? $this->withLikedState($character, $liked)
                : $character,
            $this->all(),
        );

        $this->cache->forever(self::CACHE_KEY, $characters);

        return $characters;
    }

    private function withLikedState(Character $character, bool $liked): Character
    {
        if ($character->liked === $liked) {
            return $character;
        }

        return new Character(
            id: $character->id,
            name: $character->name,
            title: $character->title,
            description: $character->description,
            likes: $character->likes + ($liked ? 1 : -1),
            liked: $liked,
        );
    }

    private function defaults(): array
    {
        return [
            new Character(
                id: 1,
                name: 'Frieren',
                title: 'Mage of the Hero Party',
                description: 'Quiet, deliberate, and still collecting small magical comforts.',
                likes: 312,
                liked: false,
            ),
            new Character(
                id: 2,
                name: 'Fern',
                title: 'Disciplined apprentice mage',
                description: 'Practical, precise, and usually the first to notice trouble.',
                likes: 246,
                liked: false,
            ),
            new Character(
                id: 3,
                name: 'Stark',
                title: 'Front-line warrior',
                description: 'Brave when it matters, even if he worries first.',
                likes: 198,
                liked: false,
            ),
            new Character(
                id: 4,
                name: 'Himmel',
                title: 'Hero of the party',
                description: 'A remembered kindness that keeps shaping the journey.',
                likes: 284,
                liked: false,
            ),
        ];
    }
}
