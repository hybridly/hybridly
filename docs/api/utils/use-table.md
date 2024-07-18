---
outline: deep
---

# `useTable`

This composable is used to manipulate tables and build their user interface.

| Related | [Tables](../../guide/tables.md) |
| ------- | ------------------------------- |

## Usage

```ts
function useTable<
  Properties extends Record<string, any>,
	Paginator extends 'simple' | 'cursor' | 'length-aware',
>(
	properties: Properties,
	tableKey: keyof Properties,
	defaultOptions: HybridRequestOptions = {}
)
```

`useTable` accepts the view's `$props` object as its first parameter and the name of the `Table` object's property as the second. As its optional third parameter, it accepts a list of [request options](../router/options.md).

## Example

```ts
const $props = defineProps<{
	users: Table<App.Data.UserData>
}>()

const users = useTable($props, 'users')
```

## Records

The `records` property returns a computed list of `Record` objects with the following properties:

```vue-html
<tr v-for="{ key, value } in users.records" :key="key">
	<td
		v-for="column in users.columns"
		:key="column.name"
		v-text="value(column)"
	/>
</tr>
```

### `value`

- Type: `Function`
- Parameters: `column: Column`

Gets the value of the record for the specified column.

### `extra`

- Type: `Function`
- Parameters: `column: Column` and `path: string`

Gets the extra data of the record for the specified column. The second parameter is the dot-notation-enabled path to the extra property you want to access.

### `key`

- Type: `string | int`

The key of the record. Generally, it is the value of the `id` column. It is used for executing actions, but may be missing if [actions are disabled](../../guide/tables.md#disabling-actions-globally).

### `execute`

- Type: `Function`
- Parameters: `action: Action`

Executes the given inline action for the record.

### `actions`

- Type: `Action[]`

The list of inline actions. Each `Action` has an additional scoped `execute` function.

### `select`

- Type: `Function`

Selects this record.

### `deselect`

- Type: `Function`

Deselects this record.

### `toggle`

- Type: `Function`

Toggles selection for this record.

### `selected`

- Type: `bool`

Checks whether this record is selected.

### `record`

- Type: `T`

The actual record. Its type is determined by the first generic of `useTable`.

## Actions

The following functions and properties are used to deal with actions. All `Action` objects use the following interface:

<<< @/../packages/vue/src/composables/table.ts#action

### `bulkActions`

- Type: `Action[]`

Returns a list of available bulk actions. Hidden actions are not part of the list.

The returned actions contain an extra `execute` function that can be used to call the action for all [selected records](#bulk-selection):

```vue-html
<div v-for="action in users.bulkActions" :key="action.name">
	<button
		@click="action.execute()/* [!code hl]*/"
		v-text="action.label"
	/>
</div>
```

### `inlineActions`

- Type: `Action[]`

Returns a list of available inline actions. Hidden actions are not part of the list.

The returned actions contain an extra `execute` function that can be used to call the action for the specified record:

```vue-html
<div v-for="action in users.inlineActions" :key="action.name">
	<button
		@click="action.execute(record)/* [!code hl]*/"
		v-text="action.label"
	/>
</div>
```

Note that the recommended way to use inline actions is through the `table.records.actions` property, which doesn't need a record to be specified.

### `executeInlineAction`

- Type: `Function`
- Parameters: `action: Action`, `record: Record`

This function executes the given inline action for the given record.

### `executeBulkAction`

- Type: `Function`
- Parameters: `action: Action`, `options?: BulkActionOptions`

This function executes the given bulk action for all [selected records](#bulk-selection).

## Columns

The `columns` property returns a computed list of `Column` objects with the following properties:

### `name`

- Type: `string`

The name of the column.

### `label`

- Type: `string`

The label of the column.

### `metadata`

- Type: `Record<string, any>`

Custom [metadata](../../guide/tables.md#adding-metadata) for the column.

### `isSortable`

- Type: `bool`

Checks whether the column has a corresponding sort.

### `toggleSort`

- Type: `Function`
- Parameters: `options?: ToggleSortOptions`

Toggles sorting for this column, provided it has a corresponding sort.

### `isSorting`

- Type: `Function`
- Parameters: `direction?: SortDirection`

Checks whether the column is being sorted.

### `isFilterable`

- Type: `bool`

Checks whether the column has a corresponding filter.

### `applyFilter`

- Type: `Function`
- Parameters: `value: any`, `options?: AvailableHybridRequestOptions`

Applies the filter with the same name as the column, if it exists.

### `clearFilter`

- Type: `Function`
- Parameters: `options?: AvailableHybridRequestOptions`

Clears the filter with the same name as the column, if it exists.

## Bulk-selection

The following functions and properties are used to deal with selecting records.

### `selection`

The `selection` objects represents the records that are selected. It uses the following interface:

<<< @/../packages/vue/src/composables/bulk-select.ts#bulk-selection

When selecting _all_ records, instead of adding all possible record identifiers in a set, the `all` boolean will be set to `true`. In this situation, records may be de-selected by being added to `except`.

This is all done _automaticaly_ by the helpers bellow.

### `selectAll`

- Type: `Function`

Selects all records.

### `deselectAll`

- Type: `Function`

De-selects all records.

### `selectPage`

- Type: `Function`

Selects all records on the current page.

### `deselectPage`

- Type: `Function`

De-selects all records on the current page.

### `isPageSelected`

- Type: `Computed<bool>`

Checks if all records on the current page are selected.

### `isSelected`

- Type: `Function`
- Parameters: `record: T`

Checks if the given record is selected.

### `toggle`

- Type: `Function`
- Parameters: `record: T`

Toggles selection for the given record.

### `select`

- Type: `Function`
- Parameters: `record: T`

Selects the given record.

### `deselect`

- Type: `Function`
- Parameters: `record: T`

Deselectsthe given record.

### `allSelected`

- Type: `Computed<bool>`

Checks if all records are selected.

## Pagination

A `paginator` object is returned. Its shape is typed and depends on the second generic of [`useTable`](#usage), which should be one of `simple`, `cursor` or `length-aware`.

You can learn more about the supported paginators in the [tables documentation](../../guide/tables.md#supported-paginators).

## Refinement

The preferred way of dealing with refinement using `useTable` is to use the scoped refinement helpers available in its properties.

However, for convenience, all the properties and functions returned by [`useRefinements`](./use-refinements.md#returned-object) are also available.
