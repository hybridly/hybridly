# `useRefinements`

This composable is part of the refining feature. It takes a `Refinement` property as a parameter and exposes methods to refine a query using filters and sorts.

## Usage

```ts
function useRefinements<
  Properties extends object,
  RefinementsKey extends keyog Properties
>(
  properties: Properties,
  refinementsKey: RefinementsKey,
  defaultOptions: HybridRequestOptions = {}
)
```

`useRefinements` accepts the page's `$props` object as its first parameter and the name of the `Refinement` object's property as the second. As its optional third parameter, it accepts a list of [request options](../router/options.md).

## Example

```ts
const $props = defineProps<{
  users: Paginator<App.Data.UserData>
  refinements: Refinements
}>()

const refine = useRefinements($props, 'refinements')
```

## Returned object

The object returned by `useRefinements` contains a few functions and properties that are used to refine the affected query. 

All of the functions below accept additional [request options](../router/options.md) as their last parameter.

### `toggleSort`

- Type: `Function`
- Parameters: `sortName: string`

Toggles the specified sort. `ToggleSortOptions` is the same as `HybridRequestOptions` with an additional `direction` property, which should be `asc` or `desc`. If specified, the sort will be applied as such.

### `applyFilter`

- Type: `Function`
- Parameters: `filter: string`, `value: any`

Applies the specified value to the specified filter.

### `clearFilter`

- Type: `Function`
- Parameters: `filter: string`

Clears the specified filter.

### `clearFilters`

- Type: `Function`

Clears all active filters.

### `clearSorts`

- Type: `Function`

Clears all active sorts.

### `reset`

- Type: `Function`

Resets all filters and sorts.

### `isSorting`

- Type: `ComputedRef<boolean>`

Specifies whether there is an active sort.

### `isFiltering`

- Type: `ComputedRef<boolean>`

Specifies whether there is an active filter.

### `currentSorts`

- Type: `Array<SortRefinement>`

The list of currently active sorts.

### `currentFilters`

- Type: `Array<FilterRefinement>`

The list of currently active filters.

### `filters`

- Type: [`Array<FilterRefinement>`](#interfaces)

The list of available filters. They are configured by the `Refine` object in the back-end.

### `sorts`

- Type: [`Array<SortRefinement>`](#interfaces)

The list of available sorts. They are configured by the `Refine` object in the back-end.

## Interfaces

The following are relevant interfaces used by `useRefinements` and its return value.

<<< @/../packages/vue/src/composables/refine.ts#interfaces
