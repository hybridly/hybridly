<?php

use Hybridly\Refining\Filters\BaseFilter;
use Hybridly\Refining\Filters\CallbackFilter;
use Hybridly\Tests\Fixtures\Database\ProductFactory;
use Hybridly\Tests\Fixtures\Filters\InvokableClassFilter;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;

it('can be serialized', function () {
    $filter = CallbackFilter::make('airpods_gen', function (Builder $builder, mixed $value) {
        $builder->where('generation', $value);
    })->metadata([
        'foo' => 'bar',
    ]);

    $serialized = $filter->jsonSerialize();

    expect($serialized)
        ->toMatchArray([
            'name' => 'airpods_gen',
            'hidden' => false,
            'label' => 'Airpods gen',
            'type' => 'callback',
            'is_active' => false,
            'value' => null,
            'default' => null,
        ]);

    expect($serialized['metadata'])->toHaveKey('foo', 'bar');
});

it('casts string to int when the callback expects an int', function () {
    ProductFactory::new()->create(['name' => 'AirPods Gen 2', 'price' => 200]);
    ProductFactory::new()->create(['name' => 'AirPods Gen 3', 'price' => 300]);

    $result = mock_refiner(
        query: ['filters' => ['min_price' => ['value' => '250']]],
        refiners: [
            CallbackFilter::make('min_price', fn (Builder $builder, int $value) => $builder->where('price', '>=', $value)),
        ],
    )->get();

    expect($result)
        ->toHaveCount(1)
        ->first()
        ->name->toBe('AirPods Gen 3');
});

it('filters according to the given callback', function () {
    ProductFactory::new()->count(10)->create();
    ProductFactory::new()
        ->count(4)
        ->sequence(
            ['name' => 'AirPods (2nd generation)'],
            ['name' => 'AirPods (3rd generation)'],
            ['name' => 'AirPods Pro (2nd generation)'],
            ['name' => 'AirPods Max'],
        )
        ->create();

    $filters = mock_refiner(
        query: ['filters' => ['airpods_gen' => ['value' => 2]]],
        refiners: [
            CallbackFilter::make(
                'airpods_gen',
                fn (Builder $builder, int $value) => match ($value) {
                    2 => $builder->where('name', 'like', '%(2nd generation)'),
                    3 => $builder->where('name', 'like', '%(3rd generation)'),
                    default => null,
                },
            ),
        ],
    );

    expect($filters)
        ->first()
        ->name
        ->toBe('AirPods (2nd generation)')
        ->count()
        ->toBe(2);
});

it('injects parameters by type and by name', function () {
    ProductFactory::new()->count(10)->create();
    ProductFactory::new()
        ->count(4)
        ->sequence(
            ['name' => 'AirPods (2nd generation)'],
            ['name' => 'AirPods (3rd generation)'],
            ['name' => 'AirPods Pro (2nd generation)'],
            ['name' => 'AirPods Max'],
        )
        ->create();

    $filters = mock_refiner(
        query: ['filters' => ['airpods_gen' => ['value' => 2]]],
        refiners: [
            CallbackFilter::make(
                'airpods_gen',
                fn (Builder $qb, int $value) => match ($value) {
                    2 => $qb->where('name', 'like', '%(2nd generation)'),
                    3 => $qb->where('name', 'like', '%(3rd generation)'),
                    default => null,
                },
            ),
        ],
    );

    expect($filters)
        ->first()
        ->name
        ->toBe('AirPods (2nd generation)')
        ->count()
        ->toBe(2);
});

it('accepts invokable classes by fqcn', function () {
    ProductFactory::new()->create(['name' => 'AirPods']);
    ProductFactory::new()->create(['name' => 'AirPods Pro']);
    ProductFactory::new()->create(['name' => 'Macbook Pro M1']);

    $filters = mock_refiner(
        query: ['filters' => ['name' => ['value' => 'AirPods Pro']]],
        refiners: [
            CallbackFilter::make('name', InvokableClassFilter::class),
        ],
    );

    expect($filters)
        ->first()
        ->name
        ->toBe('AirPods Pro')
        ->count()
        ->toBe(1);
});

it('uses the type of the invokable class', function () {
    $filter = CallbackFilter::make('name', new class() {
        public function __invoke(Builder $builder, mixed $value): void
        {
            $builder->where('name', '=', $value);
        }

        public function getType(): string
        {
            return 'custom';
        }
    });

    expect($filter)
        ->toBeInstanceOf(BaseFilter::class)
        ->jsonSerialize()
        ->toMatchArray([
            'name' => 'name',
            'hidden' => false,
            'label' => 'Name',
            'type' => 'custom',
            'metadata' => [],
            'is_active' => false,
            'value' => null,
            'default' => null,
        ]);
});

it('casts string to float when the callback expects a float', function () {
    ProductFactory::new()->create(['name' => 'Product A', 'price' => 19.99]);
    ProductFactory::new()->create(['name' => 'Product B', 'price' => 29.99]);

    $result = mock_refiner(
        query: ['filters' => ['max_price' => ['value' => '25.50']]],
        refiners: [
            CallbackFilter::make('max_price', fn (Builder $builder, float $value) => $builder->where('price', '<=', $value)),
        ],
    )->get();

    expect($result)
        ->toHaveCount(1)
        ->first()
        ->name->toBe('Product A');
});

it('casts string to bool when the callback expects a bool', function () {
    ProductFactory::new()->create(['name' => 'Available Product', 'is_active' => true]);
    ProductFactory::new()->create(['name' => 'Unavailable Product', 'is_active' => false]);

    $result = mock_refiner(
        query: ['filters' => ['is_active' => ['value' => '1']]],
        refiners: [
            CallbackFilter::make('is_active', fn (Builder $builder, bool $value) => $builder->where('is_active', $value)),
        ],
    )->get();

    expect($result)
        ->toHaveCount(1)
        ->first()
        ->name->toBe('Available Product');
});

it('casts value to string when the callback expects a string', function () {
    ProductFactory::new()->create(['name' => 'Test Product', 'price' => 123]);
    ProductFactory::new()->create(['name' => 'Another Product', 'price' => 456]);

    $result = mock_refiner(
        query: ['filters' => ['price_str' => ['value' => 123]]],
        refiners: [
            CallbackFilter::make('price_str', fn (Builder $builder, string $value) => $builder->where('price', $value)),
        ],
    )->get();

    expect($result)
        ->toHaveCount(1)
        ->first()
        ->price->toBe(123);
});

it('casts value to array when the callback expects an array', function () {
    ProductFactory::new()->create(['name' => 'AirPods']);
    ProductFactory::new()->create(['name' => 'iPhone']);
    ProductFactory::new()->create(['name' => 'iPad']);

    $result = mock_refiner(
        query: ['filters' => ['name' => ['value' => 'AirPods']]],
        refiners: [
            CallbackFilter::make('name', fn (Builder $builder, string $property, array $value) => $builder->whereIn('name', $value)),
        ],
    )->get();

    expect($result)
        ->toHaveCount(1)
        ->first()
        ->name->toBe('AirPods');
});

it('handles nullable types correctly', function () {
    ProductFactory::new()->create(['name' => 'Product A', 'description' => 'A description']);

    $result = mock_refiner(
        query: ['filters' => ['description' => ['value' => 'A description']]],
        refiners: [
            CallbackFilter::make('description', fn (Builder $builder, ?string $value) => $value === null
                ? $builder->whereNull('description')
                : $builder->where('description', $value)),
        ],
    )->get();

    expect($result)
        ->toHaveCount(1)
        ->first()
        ->name->toBe('Product A');
});

it('works with invokable classes that have typed parameters', function () {
    ProductFactory::new()->create(['name' => 'Cheap Item', 'price' => 10]);
    ProductFactory::new()->create(['name' => 'Expensive Item', 'price' => 100]);

    $filter = new class() {
        public function __invoke(Builder $builder, int $value, string $property): void
        {
            $builder->where('price', '>=', $value);
        }
    };

    $result = mock_refiner(
        query: ['filters' => ['min_price' => ['value' => '50']]],
        refiners: [
            CallbackFilter::make('min_price', $filter),
        ],
    )->get();

    expect($result)
        ->toHaveCount(1)
        ->first()
        ->name->toBe('Expensive Item');
});
