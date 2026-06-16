<?php

namespace Hybridly\Architecture;

final class JustInTimeComponentRepository implements ComponentRepository
{
    /** @var Component[] */
    private array $components {
        get => $this->components ??= $this->loader->load();
    }

    public function __construct(
        private readonly ComponentLoader $loader,
    ) {}

    public function list(ComponentType $type): array
    {
        return array_values(array_filter($this->components, static fn (Component $component) => $component->type === $type));
    }

    public function add(Component $component): static
    {
        return $this;
    }

    public function has(Component|string $component): bool
    {
        $identifier = $component instanceof Component
            ? $component->identifier
            : $component;

        return array_any($this->components, static fn (Component $component) => $component->identifier === $identifier);
    }
}
