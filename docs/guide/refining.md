---
outline: 'deep'
---

# Refining

## Overview

Refining is the concept of filtering and sorting data. Hybridly offers a first-party, declarative API for refining queries.

The refining process happens as follows:
- The available filters and sorts are declared using a `Refine` instance
- The `Refine` instance runs the query according to the current request
- The query result and the refinements are shared to the view as properties
- The view uses [`useRefinements`](../api/utils/use-refinements.md) to generate a user interface and apply sorts and filters

:::info Experimental
This feature has not been dogfed yet and is considered experimental. Its API may change at any time. Feel free to give feedback on our Discord server.
:::

## Refining a query

The `Refine` class can be instanciated using a model class name or an Eloquent builder instance.

:::code-group
```php [Model]
use App\Models\Chirp;
use Hybridly\Refining\Refine;

Refine::model(Chirp::class);
```
```php [Eloquent builder]
use App\Models\Chirp;
use Hybridly\Refining\Refine;

Refine::query(
  Chirp::query()->where('author_id', $user->id)
);
```
:::

This `Refine` instance will update the query according to the specified refiners and the current request. 

The query can then be executed as usual by chaining any Eloquent builder method, like `paginate` or `get`:

```php
$chirps = Refine::model(Chirp::class)
  ->paginate();
```

## Specifying refiners

A refiner is an object that updates the query according to the current request. 

Filters and sorts are example of built-in refiners, but you may create your own by implementing the `Hybridly\Refining\Contracts\Refiner` interface.

You may specify refiners for a query using the `with` method:

```php
use App\Models\Chirp;
use Hybridly\Refining\{Sorts, Filters}; // [!code focus]
use Hybridly\Refining\Refine;

$chirps = Refine::model(Chirp::class)->with([ // [!code focus:4]
    Sorts\Sort::make(property: 'created_at', alias: 'date'),
    Filters\TrashedFilter::make(name: 'trashed'),
]);
```

## Sharing the query

The result of the query can be obtained by calling any valid Eloquent builder method on the `Refine` instance. Similarly, the available refiners can be obtained by calling the `refinements` method.

These two objects should be shared as properties to the view:

:::code-group
```php [ChirpController.php]
public function index()
{
    $this->authorize('viewAny', Chirp::class);

    $chirps = Refine::model(Chirp::class)->with([ // [!code focus:4]
        Sorts\Sort::make('created_at', alias: 'date'),
        Filters\TrashedFilter::make(),
    ])->forHomePage();

    return hybridly('chirps.index', [ // [!code focus:4]
        'chirps' => ChirpData::collection($chirps->paginate()),
        'refinements' => $chirps->refinements(),
    ]);
}
```
```vue [index.vue]
<script setup lang="ts">
const $props = defineProps<{ // [!code focus:4]
	chirps: Paginator<App.Data.ChirpData>
	refinements: Refinements
}>()

const refine = useRefinements($props, 'refinements') // [!code focus]
</script>
```
:::

## Applying filters and sorts

The [`useRefinements`](../api/utils/use-refinements.md) composable may be used to build a user interface that allows applying refiners.

It provides methods that can be used to apply or reset filters and sorts, and it has properties that may enumerate available and current refiners.

The following example shows how to create a basic user interface using the `filters` properties:

```vue
<script setup lang="ts">
const $props = defineProps<{ // [!code focus:4]
	chirps: Paginator<App.Data.ChirpData>
	refinements: Refinements
}>()

const refine = useRefinements($props, 'refinements') // [!code focus]
</script>

<template>
  <!-- Loops through available filters --> // [!code focus:21]
  <div v-for="filter in refine.filters" :key="filter.name">

    <!-- Shows a `text` input for the filter named `body` -->
    <template v-if="filter.name === 'body'">
      <input
        type="text"
        @change="filter.apply($event.target.value)"
      />
    </template>

    <!-- Shows a `select` input for the "trashed" filter -->
    <template v-if="filter.type === 'trashed'">
      <select @change="filter.apply($event.target.value)">
        <option value="with" :selected="filter.value === 'with'">All</option>
        <option value="only" :selected="filter.value === 'only'">Trashed</option>
        <option value="" :selected="!filter.value">Not trashed</option>
      </select>
    </template>

  </div>
</template>
```

## Querying nested relationships

Filters have basic relationship filtering capabilities, which means you may use the dot-notation syntax to specify a property from a relationship.

```php
// ?filters[user]=jon
Filters\Filter::make('user.full_name', alias: 'user');
```

It is recommended to specify an alias when filtering relationship properties, otherwise the filter name will have its `.` replaced by underscores. 

Note that filters using relationship use `whereHas` under the hood, which might not be the best option performance-wise.

:::warning Sorts are not supported
Note that the provided sorts do not support relationships. You will need to use a custom sort with a subquery to achieve a relationship sort.
:::


## Using an alias

It may not be desirable to expose the name of a database column to users. You may use the `alias` argument to specify a name that will identify a refiner:

```php
// ?sort=date
Sorts\Sort::make('created_at', alias: 'date');
```

In the example above, `date` is used to apply the sort instead of the column name `created_at`. 

Note that certain refiners, like `TrashedFilter` or `CallbackFilter`, cannot have an alias as they don't use the specified property in their query.

## Available filters

### `Filter`

This filter will use the provided column to find a match using a `WHERE column = ?` or a `WHERE column LIKE ?` statement.

#### Strict comparisons

By default, `Filter` will do a strict comparison using a `WHERE column ?` statement:

```php
// ?filters[user_id]=1  ->  WHERE user_id = 1
Filters\Filter::make('user_id');
```

#### Loose comparisons

You may call the `loose`, `beingsWithStrict` or `endsWithStrict` methods to specify which kind of comparison the filter should use.

```php
// ?filters[full_name]=Jon  ->  WHERE full_name LIKE %jon%
Filters\Filter::make('full_name')->loose();

// ?filters[full_name]=Jon  ->  WHERE full_name LIKE Jon%
Filters\Filter::make('full_name')->beingsWithStrict();

// ?filters[full_name]=Doe  ->  WHERE full_name LIKE %Doe
Filters\Filter::make('full_name')->endsWithStrict();
```

#### Using another operator

You may change the operator by specifying it through the `operator` method:

```php
// ?filters[full_name]=Jon  ->  WHERE full_name NOT LIKE %jon%
Filters\Filter::make('full_name')
	->operator('NOT LIKE')
	->loose();

// ?filters[user_id]=1      ->  WHERE user_id != 1
Filters\Filter::make('user_id')->operator('!=');
```

#### Using an enum

You may call the `enum` method to specify a backed enum class that will validate the property. If the property doesn't match one of the enum values, the filter will not apply.

```php
// ?filters[company]=apple   ->  Filter applies
Filters\Filter::make('company')->enum(Company::class);

// ?filters[company]=foobar  ->  Filter will not apply
Filters\Filter::make('company')->enum(Company::class);
```

### `SelectFilter`

This filter will perform a `where` statement on the value provided by the `options` array. If the value is not found in the provided list, the filter will not apply.

#### Key-value options

If the options provided are a list of key-value pairs, the key represents the query parameter name, and the value for the column name:

```php
// ?filters[os]=iphone -> `WHERE os = 'ios'`
Filters\SelectFilter::make('os', options: [
  'iphone' => 'ios',
  'ipad' => 'ipados',
  'samsung' => 'android',
]);
```

#### List options

If the option array is a list, the key will be used for both the query parameter and the column name:

```php
// ?filters[os]=ios -> `WHERE os = 'ios'`
Filters\SelectFilter::make('os', options: [
  'ios',
  'ipados',
  'android',
]);
```

#### Enum options

Alternatively, you may provided a backed enum as the options.

```php
use App\Enums\OperatingSystem;

// ?filters[os]=ios -> `WHERE os = 'ios'`
Filters\SelectFilter::make('os', OperatingSystem::class);
```

### `TrashedFilter`

This filter will include or exclude soft-deleted records:

```php
// ?filters[trashed]=only
Filters\TrashedFilter::make();
```

### `BooleanFilter`

This filter will convert the request's value to a boolean value to perform a boolean `where` statement:

```php
// ?filters[is_active]=true
// ?filters[is_active]=1
// ?filters[is_active]=y
Filters\BooleanFilter::make('is_active');
```

## Available sorts

### `Sort`

This sort will sort the records by the specified field:

```php
// ?sort=created_at
Sorts\Sort::make('created_at');
```

## Custom filters and sorts

The provided filters are relatively basic and will not suit every situation, notably the ones where relationships are involved.

You may use the provided `CallbackFilter` to implement your own filter using a closure or an invokable class:

```php
// Invokable class
CallbackFilter::make('own_chirps', OwnChirpsFilter::class);

// Callback function
CallbackFilter::make(
	name: 'own_chirps',
	callback: function (Builder $builder, mixed $value, string $property) {
			$builder->where('author_id', auth()->id());
	}
);
```

Similary, the `CallbackSort` class can be used to implement a custom sort:

```php
// Invokable class
CallbackSort::make('date', DateSort::class);

// Callback function
CallbackSort::make(
	name: 'date',
	callback: function (Builder $builder, string $direction, string $property) {
			$builder->orderBy('created_at', $direction);
	}
);
```
