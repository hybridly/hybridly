# Refining

## Overview

Refining is the concept of filtering and sorting data. Hybridly offers a first-party, declarative API for refining queries.

The refining process happens as follows:
- The available filters and sorts are declared using a `Refine` instance
- The `Refine` instance runs the query according to the current request
- The query result and the refinements are shared to the view as properties
- The view uses [`useRefinements`](../api/composables/use-refinements.md) to generate a user interface and apply sorts and filters

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
    Sorts\FieldSort::make(property: 'created_at', alias: 'date'),
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
        Sorts\FieldSort::make('created_at', alias: 'date'),
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

The [`useRefinements`](../api/composables/use-refinements.md) composable may be used to build a user interface that allows applying refiners.

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
        @change="(e) => refine.applyFilter(filter.name, e.target.value)"
      />
    </template>

    <!-- Shows a `select` input for the "trashed" filter -->
    <template v-if="filter.type === 'trashed'">
      <select @change="(e) => refine.applyFilter(filter.name, e.target.value)">
        <option value="with" :selected="filter.value === 'with'">All</option>
        <option value="only" :selected="filter.value === 'only'">Trashed</option>
        <option value="" :selected="!filter.value">Not trashed</option>
      </select>
    </template>

  </div>
</template>
```

## Using an alias

It may not be desirable to expose the name of a database column to users. You may use the `alias` argument to specify a name that will identify a refiner:

```php
// ?sort=date
Sorts\FieldSort::make('created_at', alias: 'date');
```

In the example above, `date` is used to apply the sort instead of the column name `created_at`.

## Available filters and sorts

### `ExactFilter`

This filter will use the provided column to find an exact match using a `where column = ?` statement:

```php
// ?filters[user_id]=1
Filters\ExactFilter::make('user_id')
```

### `TrashedFilter`

This filter will include or exclude soft-deleted records:

```php
// ?filters[trashed]=only
Filters\TrashedFilter::make()
```

### `SimilarityFilter`

This filter will use the `LIKE` operator to find records depending on the specified mode.


```php
// WHERE full_name LIKE '%jon%'
Filters\SimilarityFilter::make('full_name', mode: SimilarityFilter::LOOSE)

// WHERE full_name LIKE '%jon'
Filters\SimilarityFilter::make('full_name', mode: SimilarityFilter::BEGINS_WITH_STRICT)

// WHERE full_name LIKE 'doe%'
Filters\SimilarityFilter::make('full_name', mode: SimilarityFilter::ENDS_WITH_STRICT)
```

### `FieldSort`

This sort will sort the records by the specified field:

```php
// ?sort=created_at
Sorts\FieldSort::make('created_at')
```

## Custom filters and sorts

The provided filters are relatively basic and will not suit every situation, notably the ones where relationships are involved.

You may use the provided `CallbackFilter` to implement your own filter using a closure or an invokable class:

```php
Refine::model(Chirp::class)->with([
    Filters\CallbackFilter::make('own_chirps', OwnChirpsFilter::class),
    Filters\CallbackFilter::make('own_chirps', function (Builder $builder, mixed $value, string $property) {
        $builder->where('author_id', auth()->id());
    }),
]);
```

Similary, the `CallbackSort` class can be used to implement a custom sort:

```php
Refine::model(Chirp::class)->with([
    Sorts\CallbackSort::make('date', DateSort::class),
    Sorts\CallbackSort::make('date', function (Builder $builder, string $direction, string $property) {
        $builder->orderBy('created_at', $direction);
    }),
]);
```

Additionally, you may pass any custom parameter to the callbacks or invokable classes:

```php
Refine::model(Chirp::class)->with([
    Sorts\CallbackSort::make(
        name: 'date',
        callback: function (Builder $builder, string $direction, string $foo) {
            // $foo = 'bar'
            $builder->orderBy('created_at', $direction);
        },
        parameters: [
          'foo' => 'bar'
        ]
      ),
]);
```
