---
outline: 'deep'
---

# Tables

## Overview

Hybridly provides a way to describe tables on the back-end and manipulate them through the [`useTable`](../api/utils/use-table.md) util on the front-end.

Tables provide the ability to execute actions on one or multiple records, to filter and sort them using [refinements](./refining.md), have [data objects](#using-data-objects) integration, support pagination, scoping, and let you have full control over the user interface.

:::info Experimental
This feature has not been dogfed yet and is considered experimental. Its API may change at any time. Feel free to give feedback on our Discord server.
:::

## Creating and using tables

### Generating the table class

A table is defined by a class that extends `Hybridly\Tables\Table`. The associated model is inferred by the class name, but can be specified by using the `$model` class property.

```php
use App\Models\User;
use Hybridly\Tables\Columns\TextColumn;
use Hybridly\Tables\Table;

final class UsersTable extends Table
{
    protected string $model = User::class;

    protected function defineColumns(): array
    {
        return [
            TextColumn::make('id')->label('#'),
            TextColumn::make('full_name')->transformValueUsing(fn (User $user) => "{$user->first_name} {$user->last_name}"),
        ];
    }
}
```

As a convenience, you may use the provided `make:table` command to generate a table:

```
php artisan make:table UsersTable
```

Optionally, you may provide the `--model` argument to specify the associated model.

### Accessing tables in views

Like refinements, you may simply pass the table as a property to the hybrid view returned by a controller:

```php
use function Hytbridly\view;

return view('users.index', [
	'users' => UsersTable::make(),
]);
```

The table property can be typed using the global `Table` type, which also accepts a generic that describes the table shape:

```ts
const $props = defineProps<{
	users: Table<{
		id: number
		full_name: string
	}>
}>()

const users = useTable($props, 'users')
```

At its simplest, the template for a table may look like that:

```vue
<template>
	<table>
		<tbody>
			<tr v-for="{ key, value } in users.records" :key="key"> <!-- [!code focus:6] -->
				<td
					v-for="column in users.columns"
					:key="column.name"
					v-text="value(column)"
				/>
			</tr>
		</tbody>
	</table>
</template>
```

:::tip User interface
The user interface is completely up to you, no component is provided. You may refer to the [`useTable`](../api/utils/use-table.md) documentation to see which utilities are available to work with tables.
:::

### Multiple tables

It is possible to work with multiple tables in the same view, but for filters and pagination to work, they need to be scoped.

This can be done by specifying the `$scope` class property:

:::code-group
```php [UsersTable.php]
use App\Models\User;
use Hybridly\Tables\Table;

final class UsersTable extends Table
{
    protected string $model = User::class;
    protected string $scope = 'users';

    // ...
}
```
```php [ProjectsTable.php]
use App\Models\Project;
use Hybridly\Tables\Table;

final class ProjectsTable extends Table
{
    protected string $model = Project::class;
    protected string $scope = 'projects';

    // ...
}
```
```php [Controller.php]
use function Hytbridly\view;

return view('dashboard', [
	'users' => UsersTable::make(),
	'projects' => ProjectsTable::make(),
]);
```
```ts [dashboard.vue]
const $props = defineProps<{
	users: Table<App.Data.UserData>
	projects: Table<App.Data.ProjectData>
}>()

const users = useTable($props, 'users')
const projects = useTable($props, 'projects')
```
:::

When scoping tables, refining records and pagination will automatically work through the utilities provided by [`useTable`](../api/utils/use-table.md).

## Working with columns

### Defining columns

Columns specify what record properties will be available on the front-end. They are defined inside the `defineColumns` method, using the `TextColumn` class:

```php
use Hybridly\Tables\Columns\TextColumn; // [!code focus]

protected function defineColumns(): array  // [!code focus:8]
{
		return [
				TextColumn::make('id')->label('#'),
				TextColumn::make('full_name'),
				TextColumn::make('email'),
		];
}
```

The name passed to the `make` constructor should refer to a valid model property.

### Specifying labels

By default, the label is generated from the column name. You may customize it using the `label` method:

```php
TextColumn::make('full_name')
	->label('Name')
```

You may access the label in the `column` object:

```vue-html
<th v-for="column in users.columns" :key="column.name">
	<span v-text="column.label" />
</th>
```

### Transforming column values

It is often desirable to transform the value of a column. To achieve this, you may pass a callback to the `transformValueUsing` function:

```php
TextColumn::make('full_name')
    ->transformValueUsing(function (User $user) {
        return "{$user->first_name} {$user->last_name}";
    })
```

The value of a column is accessible in the `records` property of the table:

```vue-html
<tr v-for="{ key, value } in users.records" :key="key">
	<td
		v-for="column in users.columns"
		:key="column.name"
		 v-text="value(column)/* [!code hl] */"
	/>
</tr>
```

### Hiding columns

You may dynamically hide columns by passing a boolean or callback to the `hidden` function:

```php
TextColumn::make('id')
	->hidden(fn () => ! auth()->user()->is_admin)
```

Hidden columns **are not transmitted to the front-end at all**, and their corresponding model properties will not be available. If you need to hide a column but still have access to its properties, you may use [metadata](#adding-metadata) instead.

### Adding metadata

You may pass any information to a column by passing an array to the `metadata` function. Note that the metadata applies to the actual column object, not the properties of the records.

:::code-group
```php [UsersTable.php]
TextColumn::make('full_name')->metadata([
    'color' => 'primary'
])
```
```vue-html [index.vue]
<tr v-for="{ key, value } in users.records" :key="key">
	<td
		v-for="column in users.columns"
		:key="column.name"
		:class="{
			'text-primary': column.metadata.color === 'primary'  // [!code hl]
		}"
		v-text="value(column)"
	/>
</tr>
```
:::

## Refining records

Filtering and sorting tables records works by leveraging the existing [refining](./refining.md) features. You can define or apply refiners using the same API on both the back-end and front-end.

### Defining refiners

You may define the available filters and sorts for a table by specifying [refiners](./refining.md#specifying-refiners) in the `defineRefiners` method:

```php
use Hybridly\Refining\{Filters, Sorts}; // [!code focus]

protected function defineRefiners(): array  // [!code focus:7]
{
		return [
				Sorts\Sort::make('id'),
				Filters\Filter::make('full_name'),
		];
}
```

### Applying refiners

The refining utilities returned by [`useTable`](../api/utils/use-table.md) are the same as the ones returned by [`useRefinements`](../api/utils/use-refinements.md).

For instance, you may generate the user interface for a [similarity filter](./refining.md#loose-comparisons) using the following:

```vue-html
<!-- Loop through existing filters -->
<div v-for="filter in users.filters" :key="filter.name">
	<!-- Find the filter by its type ("similar:loose" here) and build it -->
	<input
		v-if="filter.type.startsWith('similar')"
		type="text"
		@input="filter.apply(($event.target as HTMLInputElement).value)"
	/>
</div>
```

### Transforming the base query

The base query is automatically generated from the underlying model. You may override the `defineQuery` method to customize it entirely:

```php
use Illuminate\Contracts\Database\Eloquent\Builder;

protected function defineQuery(): Builder
{
		return $this->getModel()->query();
}
```

## Working with actions

Hybridly supports inline and bulk actions. Inline actions can be used to execute code for a specific record, while bulk-actions can execute code for multiple records at once.

### Defining actions

Both inline and bulk actions are defined in the `defineActions` method:

```php
use Hybridly\Tables\Actions\{InlineAction, BulkAction};
use Illuminate\Database\Eloquent\Collection;

protected function defineActions(): array
{
    return [
        InlineAction::make('delete')
					->action(fn (User $user) => $user->delete()),
        BulkAction::make('delete')
					->action(fn (Collection $records) => $records->each->delete()),
		];
}
```

The `action` method accepts a callback that will be executed when the action is called from the front-end. Dependencies from the container may be injected to that callback.

### Inline actions

The callback for inline actions accepts the typed record as a parameter. When not specifying types, the parameter _must_ be named `$record`.

```php
InlineAction::make('delete')->action(fn (User $user) => $user->delete())
InlineAction::make('delete')->action(fn ($record) => $record->delete())
```

### Bulk actions

The callback for bulk actions accepts a `Collection` parameter with any name, or a `$records` parameter if not typed.

```php
BulkAction::make('delete')
    ->action(fn (Collection $records) => $records->each->delete())
```

However, when selecting a lot of records, it may be inefficient to load them all in memory. For this reason, you may inject a `Builder` instance instead:

```php
use Illuminate\Contracts\Database\Eloquent\Builder;

BulkAction::make('delete')
    ->action(fn (Builder $query) => $query->delete())
```

### Selecting records

The `useTable` function returns utilities to select records. The selected records are scoped to the `useTable` call, so all bulk actions will use them.

To let users select records, you may use [form input binding](https://vuejs.org/guide/essentials/forms.html#checkbox) on [`selection.only`](../api/utils/use-table.md#selection):

```vue-html
<tr v-for="{ key, value, actions } in users.records" :key="key">
	<td>
		<input
			v-model="users.selection.only/* [!code hl] */"
			:value="key/* [!code hl] */"
			type="checkbox"
		/>
	</td>
	<!-- ... -->
</tr>
```

### Automatically de-selecting records

By default, executing a bulk-action will de-select all records. You may change this behavior by calling the `keepSelected` method on an action:

```php
BulkAction::make('archive')
	->keepSelected()
	->action(fn (Collection $records) => $records->each->archive()),
```

### Hiding actions

Like columns, actions may be hidden depending on a specific condition.

```php
InlineAction::make('delete')
    ->action(fn (User $user) => $user->delete())
    ->hidden(fn () => ! auth()->user()->is_admin)
```

Hidden actions are not sent to the front-end and cannot be executed, even when manually calling the action endpoint.

### Using actions

Actions work by making a `POST` hybrid request to a dedicated endpoint. The [`useTable`](../api/utils/use-table.md) util returns dedicated functions to access and execute inline and bulk actions:

:::code-group
```vue-html [bulk-actions.vue]
<div v-for="action in users.bulkActions" :key="action.name">
	<button
		@click="action.execute()/* [!code hl] */"
	/>
</div>
```
```vue-html [inline-actions.vue]
<tr v-for="{ key, actions } in users.records" :key="key">
	<!-- ... -->
	<td>
		<button
			v-for="action in actions"
			:key="action.name"
			 @click="action.execute()/* [!code hl] */"
			v-text="action.label"
		/>
	</td>
</tr>
```
:::

You may learn about all utilities in [their documentation](../api/utils/use-table.md#actions).

### Action responses

Actions use hybrid requests. By default, all callbacks will simply redirect back.

However, you may want to [redirect](./responses.md#external-redirects) to a specific URL, open a [dialog](./dialogs.md), or return any valid [hybrid response](./responses.md).

### Disabling actions globally

If, for some reason, you do not want to use actions at all, you may disable them by setting the `hybridly.tables.enable_actions` configuration option to `false`.

In this case, the action endpoint will not be registered.

## Transforming records

### Using data objects

Tables integrate well with [`laravel-data`](https://spatie.be/docs/laravel-data/v3/introduction). By specifying the `$data` class property, the records will be a paginated collection of that data object instead of being a classic Laravel paginator:

```php
use App\Models\User;
use App\Data\UserData;
use Hybridly\Tables\Table;

final class UsersTable extends Table
{
    protected string $model = User::class;
		protected string $data = UserData::class;
}
```

This can be used to [generate types](./typescript.html#transforming-php-to-typescript) and benefit from type safety on the front-end:

```ts
const $props = defineProps<{
	users: Table<App.Data.UserData>
}>()
```

You also have the ability to customize the `Data` record's resolution logic. For example, if you need to exclude authorizations (on the root record or nested records) when rendering a table:

```php
use App\Models\User;
use App\Data\UserData;
use Hybridly\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelData\Data;

final class UsersTable extends Table
{
    protected string $model = User::class;
		protected string $data = UserData::class;

    protected function resolveDataRecord(Model $model): Data
    {
        return $this->data::from($model)->exclude('authorization', 'nested.authorization');
    }
}
```

### Hooking into the paginator

In certain scenarios, you may want to hook into the paginator to manually transform the records.

It is possible to do so by overriding the `transformRecords` method, which is called just before serialization:

```php
use Illuminate\Contracts\Pagination\Paginator;

protected function transformRecords(Paginator $paginator): Paginator
{
		return $paginator->through(function (array $data) {
			// ...
		});
}
```

### Adding extra data to cells

You may pass any arbitrary data to a cell by passing a callback to the `extra` method.

:::code-group
```php [UsersTable.php]
TextColumn::make('first_name')
	->extra(fn (User $user) => [
		'tooltip' => "{$user->full_name}"
	])
```
```vue-html [index.vue]
<tr v-for="{ key, value, extra } in users.records" :key="key">
	<td
		v-for="column in users.columns"
		:key="column.name"
		v-text="value(column)"
		:title="extra(column, 'tooltip')/* [!code hl] */"
	/>
</tr>
```
:::

## Using different paginators

By default, records will be paginated using the length-aware paginator. This behavior can be modified by specifying the `$paginatorType` property:

```php
use App\Models\User;
use Hybridly\Tables\Table;
use Illuminate\Contracts\Pagination\CursorPaginator; // [!code focus]

final class UsersTable extends Table
{
    protected string $model = User::class;
    protected string $paginatorType = CursorPaginator::class;  // [!code focus]

    // ...
}
```

### Typing paginators

When using a different paginator type, you should specify it as the second generic of the `Table` type to benefit from proper autocompletion:

```ts
const $props = defineProps<{
	users: Table<App.Data.UserData, 'cursor'> // [!code hl]
}>()

const users = useTable($props, 'users')

// users.paginator.meta.next_cursor
```

### Supported paginators

The following paginator contracts and `Table` generics are supported:

| Contract                                               | Generic                  |
| ------------------------------------------------------ | ------------------------ |
| `Illuminate\Contracts\Pagination\LengthAwarePaginator` | `length-aware` (default) |
| `Illuminate\Contracts\Pagination\CursorPaginator`      | `cursor`                 |
| `Illuminate\Contracts\Pagination\Paginator`            | `simple`                 |
