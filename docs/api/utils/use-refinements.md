---
outline: deep
---

# `useRefinements`

This composable is part of the refining feature. It takes a `Refinement` property as a parameter and exposes methods to refine a query using filters and sorts.

| Related | [Refining](../../guide/refining.md) |
| ------- | ----------------------------------- |

## Usage

```ts
function useRefinements<
  Properties extends object,
  RefinementsKey extends keyof Properties
>(
  properties: Properties,
  refinementsKey: RefinementsKey,
  defaultOptions: HybridRequestOptions = {}
)
```

`useRefinements` accepts the view's `$props` object as its first parameter and the name of the `Refinement` object's property as the second. As its optional third parameter, it accepts a list of [request options](../router/options.md).

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

### `sorts`

- Type: see the [sorts array](#the-sorts-array).

The list of available sorts. They are configured by the `Refine` object in the back-end.

### `filters`

- Type: see the [filters array](#the-filters-array).

The list of available filters. They are configured by the `Refine` object in the back-end.

### `bindFilter`

- Type: `<T>(name: string, options?: BindOptions) => Ref<T>`

Binds the given filter to a ref. The second parameter, `options`, accepts an alternative `watch` function and a `debounce` property that defaults to 250 milliseconds.

**Example**

```ts
const commercial = refine.bindFilter<bool>('commercial')
const search = refine.bindFilter<string>('search')
```

### `toggleSort`

- Type: `Function`
- Parameters: `sortName: string`

Toggles the specified sort. `ToggleSortOptions` is the same as `HybridRequestOptions` with an additional `direction` property, which must be undefined, `asc` or `desc`. If specified, the sort will be applied as such.

### `applyFilter`

- Type: `Function`
- Parameters: `filter: string`, `value: any`

Applies the specified value to the specified filter.

### `isFiltering`

- Type: `Function`

Determines whether the specified filter is active.

### `clearFilter`

- Type: `Function`
- Parameters: `filter: string`

Clears the specified filter.

### `clearFilters`

- Type: `Function`

Clears all active filters.

### `isSorting`

- Type: `Function`

Toggles the specified sort. The second parameter accepts a `direction` property that specifies the direction of the sort. 

Additionnally, the `sortData` property can be used to define additionnal properties that will be added to the request only when the sort is active.

```ts
// ?sort=foo&type=bar
await refine.toggleSort('foo', {
	sortData: {
		type: 'bar',
	},
})
```

### `clearSorts`

- Type: `Function`

Clears all active sorts.

### `currentSorts`

- Type: `Array<SortRefinement>`

The list of currently active sorts.

### `currentFilters`

- Type: `Array<FilterRefinement>`

The list of currently active filters.

### `getFilter`

- Type: `(name: string): FilterRefinement|undefined`

Gets a filter object by name.

### `getSort`

- Type: `(name: string): SortRefinement|undefined`

Gets a sort object by name.

### `reset`

- Type: `Function`

Resets all filters and sorts.

## The `sorts` array

This array contains an entry for each available sort. 

Each entry extends [`SortRefinement`](#interfaces) and adds the `toggle`, `isSorting` and `clear` methods. 

These methods are shorthands to [`toggleSort`](#togglesort), [`isSorting`](#issorting) and [`clearSort`](#clearsort) respectively, without the need for the sort name parameter.

## The `filters` array

This array contains an entry for each available filter. 

Each entry extends [`FilterRefinement`](#interfaces) and adds the `apply` and `clear` methods. 

These methods are shorthands to [`applyFilter`](#applyfilter) and [`clearFilter`](#clearfilter) respectively, without the need for the filter name parameter.

## Interfaces

The following are relevant interfaces used by `useRefinements` and its return value.

<<< @/../packages/vue/src/composables/refinements.ts#interfaces
