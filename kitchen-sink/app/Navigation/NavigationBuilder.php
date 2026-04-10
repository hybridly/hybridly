<?php

declare(strict_types=1);

namespace App\Navigation;

use Illuminate\Container\Attributes\Singleton;
use Illuminate\Http\Request;

#[Singleton]
final class NavigationBuilder
{
    /** @var list<NavigationItem> */
    private array $sidebar_items = [];

    public function __construct(
        private readonly Request $request,
    ) {}

    public function addSidebarItem(NavigationItem $item): self
    {
        $this->sidebar_items[] = $item;

        return $this;
    }

    /**
     * @return list<SharedNavigationItem>
     */
    public function getSidebarItems(): array
    {
        return array_values(array_map(
            callback: fn (NavigationItem $item) => $this->transformItem($item),
            array: $this->getVisibleItems($this->sidebar_items),
        ));
    }

    /**
     * @return list<SharedBreadcrumb>
     */
    public function getBreadcrumbs(): array
    {
        $breadcrumb_path = $this->findBreadcrumbPath(
            items: $this->getVisibleItems($this->sidebar_items),
        );

        return array_values(array_map(
            callback: fn (NavigationItem $item) => new SharedBreadcrumb(
                label: $item->label,
                href: $item->href,
            ),
            array: $breadcrumb_path,
        ));
    }

    private function transformItem(NavigationItem $item): SharedNavigationItem
    {
        $children = $this->getVisibleItems($item->children);

        return new SharedNavigationItem(
            label: $item->label,
            description: $item->description,
            icon: $item->icon,
            href: $item->href,
            external: $item->external,
            active: $this->isActive($item),
            shortcuts: $item->shortcuts,
            slot: $item->slot,
            children: array_map(
                callback: fn (NavigationItem $item) => $this->transformItem($item),
                array: $children,
            ),
        );
    }

    private function isActive(NavigationItem $item): bool
    {
        if ($this->request->route()?->named(...$item->route_patterns)) {
            return true;
        }

        foreach ($this->getVisibleItems($item->children) as $child) {
            if (! $this->isActive($child)) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * @param list<NavigationItem> $items
     * @param list<NavigationItem> $parent_path
     * @return list<NavigationItem>
     */
    private function findBreadcrumbPath(array $items, array $parent_path = []): array
    {
        foreach ($items as $item) {
            $current_path = [...$parent_path, $item];

            if ($this->request->route()?->named(...$item->route_patterns)) {
                return $current_path;
            }

            $children = $this->getVisibleItems($item->children);

            if ($children === []) {
                continue;
            }

            $child_path = $this->findBreadcrumbPath(
                items: $children,
                parent_path: $current_path,
            );

            if ($child_path === []) {
                continue;
            }

            return $child_path;
        }

        return [];
    }

    /**
     * @param list<NavigationItem> $items
     * @return list<NavigationItem>
     */
    private function getVisibleItems(array $items): array
    {
        return array_values(array_filter(
            array: $items,
            callback: fn (NavigationItem $item) => $item->isVisible(),
        ));
    }
}
