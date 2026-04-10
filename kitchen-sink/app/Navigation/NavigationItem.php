<?php

declare(strict_types=1);

namespace App\Navigation;

final readonly class NavigationItem
{
    /**
     * @param list<string> $route_patterns
     * @param list<string> $shortcuts
     * @param \Closure(): bool|null $visible
     * @param NavigationItem[] $children
     */
    public function __construct(
        public string $label,
        public ?string $description = null,
        public ?string $icon = null,
        public ?string $href = null,
        public bool $external = false,
        public array $route_patterns = [],
        public array $shortcuts = [],
        public ?\Closure $visible = null,
        public ?string $slot = null,
        public array $children = [],
    ) {}

    public function isVisible(): bool
    {
        if ($this->visible === null) {
            return true;
        }

        return ($this->visible)();
    }
}
