<?php

namespace Hybridly\Architecture;

interface ComponentRepository
{
    /**
     * Lists components of the given type.
     *
     * @return Component[]
     */
    public function list(ComponentType $type): array;

    /**
     * Registers a component.
     */
    public function add(Component $component): static;

    /**
     * Checks if the given component is registered.
     *
     * @param Component|string $component The component or its fully-qualified identifier.
     */
    public function has(Component|string $component): bool;
}
