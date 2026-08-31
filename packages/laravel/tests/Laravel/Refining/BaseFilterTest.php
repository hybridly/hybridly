<?php

use Hybridly\Configuration\Configuration;
use Hybridly\Refining\Filters\BaseFilter;
use Hybridly\Refining\Filters\CallbackFilter;
use Hybridly\Refining\Filters\Operator;
use Hybridly\Refining\Filters\TextFilter;
use Hybridly\Refining\FilterState;
use Hybridly\Refining\RefinementState;
use Hybridly\Tests\Fixtures\Database\ProductFactory;
use Illuminate\Contracts\Database\Eloquent\Builder;

beforeEach(function () {
    ProductFactory::new()->create(['name' => 'AirPods']);
    ProductFactory::new()->create(['name' => 'AirPods Pro']);
    ProductFactory::new()->create(['name' => 'Macbook Pro M1']);

    $this->filter = new class() {
        public function __invoke(Builder $builder, mixed $value): void
        {
            $builder->where('name', '=', $value);
        }

        public function getType(): string
        {
            return 'callback';
        }
    };
});

test('filters can have a default value', function () {
    $filters = mock_refiner(
        refiners: [
            CallbackFilter::make('name', $this->filter)->default('AirPods Pro'),
        ],
    );

    expect($filters)
        ->first()
        ->name
        ->toBe('AirPods Pro')
        ->count()
        ->toBe(1);
});

test('filters serialize non-null defaults as explicitly configured', function () {
    expect(CallbackFilter::make('name', $this->filter)->default('AirPods Pro')->jsonSerialize())
        ->toMatchArray([
            'default' => 'AirPods Pro',
            'has_default' => true,
        ]);
});

test('filters without default do not apply absent request input', function () {
    $received = 'not-called';

    mock_refiner(
        refiners: [
            CallbackFilter::make('name', function (Builder $builder, mixed $value) use (&$received): void {
                $received = $value;
            }),
        ],
        apply: true,
    );

    expect($received)->toBe('not-called');
});

test('filters can have an explicit null default value', function () {
    $received = 'not-called';

    $refiner = mock_refiner(
        refiners: [
            CallbackFilter::make('name', function (Builder $builder, mixed $value) use (&$received): void {
                $received = $value;
            })->default(null),
        ],
        apply: true,
    );

    expect($received)->toBeNull();
    expect($refiner->getFilters()[0]->jsonSerialize())
        ->toMatchArray([
            'is_active' => true,
            'value' => null,
            'default' => null,
            'has_default' => true,
        ]);
});

test('filters are applied using their property', function () {
    $filters = mock_refiner(
        query: ['filters' => ['name' => ['value' => 'AirPods Pro']]],
        refiners: [
            CallbackFilter::make('name', $this->filter),
        ],
    );

    expect($filters)
        ->first()
        ->name
        ->toBe('AirPods Pro')
        ->count()
        ->toBe(1);
});

test('filters are not applied when their property is used but an alias is defined', function () {
    $filters = mock_refiner(
        query: ['filters' => ['name' => ['value' => 'AirPods Pro']]],
        refiners: [
            CallbackFilter::make('product', $this->filter),
        ],
    );

    expect($filters)->count()->toBe(3);
});

test('filters use the alias when defined', function () {
    $filters = mock_refiner(
        query: ['filters' => ['product' => ['value' => 'AirPods Pro']]],
        refiners: [
            CallbackFilter::make('product', $this->filter),
        ],
    );

    expect($filters)->count()->toBe(1);
});

test('filters can be serialized', function () {
    expect(TextFilter::make('airpods_gen'))
        ->toBeInstanceOf(BaseFilter::class)
        ->jsonSerialize()
        ->toMatchArray([
            'name' => 'airpods_gen',
            'hidden' => false,
            'label' => 'Airpods gen',
            'type' => 'text',
            'is_active' => false,
            'value' => null,
            'default' => null,
            'has_default' => false,
        ]);
});

test('filters use their alias as name when defined', function () {
    expect(TextFilter::make('airpods_gen', alias: 'airpods_generation'))
        ->toBeInstanceOf(BaseFilter::class)
        ->jsonSerialize()
        ->toMatchArray([
            'name' => 'airpods_generation',
            'hidden' => false,
            'label' => 'Airpods generation',
            'type' => 'text',
            'is_active' => false,
            'value' => null,
            'default' => null,
            'has_default' => false,
        ]);
});

test('serialization takes current state into account', function () {
    $filters = mock_refiner(
        query: ['filters' => ['product' => ['value' => 'AirPods Pro']]],
        refiners: [
            TextFilter::make('product', alias: 'product'),
        ],
        apply: true,
    );

    expect(data_get(json_decode(json_encode($filters)), 'filters.0'))
        ->toMatchArray([
            'name' => 'product',
            'label' => 'Product',
            'type' => 'text',
            'is_active' => true,
            'value' => 'AirPods Pro',
        ]);
});

test('filters key is globally configurable', function () {
    Configuration::get()->refining->filtersKey = 'product-filters';

    $filters = mock_refiner(
        query: ['product-filters' => ['name' => ['value' => 'AirPods Pro']]],
        refiners: [
            TextFilter::make('name'),
        ],
    );

    expect($filters)
        ->first()
        ->name
        ->toBe('AirPods Pro')
        ->count()
        ->toBe(1);
});

test('filters key is locally configurable', function () {
    $filters = mock_refiner(
        query: ['product-filters' => ['name' => ['value' => 'AirPods Pro']]],
        refiners: [
            TextFilter::make('name'),
        ],
    )->filtersKey('product-filters');

    expect($filters)
        ->first()
        ->name
        ->toBe('AirPods Pro')
        ->count()
        ->toBe(1);
});

test('filters key respects the scope', function () {
    $filters = mock_refiner(
        query: ['products-filtering' => ['name' => ['value' => 'AirPods Pro']]],
        refiners: [
            TextFilter::make('name'),
        ],
    )
        ->scope('products')
        ->filtersKey('filtering');

    expect($filters)
        ->first()
        ->name
        ->toBe('AirPods Pro')
        ->count()
        ->toBe(1);
});

test('replacement baselines supersede declared filter defaults', function () {
    $filters = mock_refiner(
        refiners: [
            CallbackFilter::make('name', $this->filter)->default('AirPods Pro'),
        ],
    )->withBaseline(new RefinementState(filters: [
        'name' => new FilterState(value: 'AirPods'),
    ]));

    expect($filters->pluck('name')->all())->toBe(['AirPods']);
});

test('an empty replacement baseline suppresses declared filter defaults', function () {
    $filters = mock_refiner(
        refiners: [
            CallbackFilter::make('name', $this->filter)->default('AirPods Pro'),
        ],
    )->withBaseline(new RefinementState());

    expect($filters)->count()->toBe(3);
});

test('request filters override replacement baselines and serialize effective state', function () {
    $filters = mock_refiner(
        query: ['filters' => ['name' => ['value' => 'AirPods Pro']]],
        refiners: [
            CallbackFilter::make('name', $this->filter)->default('Macbook Pro M1'),
        ],
        apply: false,
    )->withBaseline(new RefinementState(filters: [
        'name' => new FilterState(value: 'AirPods'),
    ]));

    $filters->applyRefiners();

    expect($filters->pluck('name')->all())->toBe(['AirPods Pro']);
    expect($filters->getFilters()[0]->jsonSerialize())->toMatchArray([
        'value' => 'AirPods Pro',
        'default' => 'AirPods',
        'has_default' => true,
        'is_overridden' => true,
        'is_cleared' => false,
    ]);
});

test('request filters equal to replacement baselines are not overridden', function () {
    $filters = mock_refiner(
        query: ['filters' => ['name' => ['value' => 'AirPods']]],
        refiners: [CallbackFilter::make('name', $this->filter)],
        apply: false,
    )->withBaseline(new RefinementState(filters: [
        'name' => new FilterState(value: 'AirPods'),
    ]));

    $filters->applyRefiners();

    expect($filters->getFilters()[0]->jsonSerialize())->toMatchArray([
        'is_overridden' => false,
        'is_cleared' => false,
    ]);
});

test('filters can explicitly clear effective baselines', function () {
    $filters = mock_refiner(
        query: ['filters' => ['name' => ['disabled' => true]]],
        refiners: [CallbackFilter::make('name', $this->filter)],
        apply: false,
    )->withBaseline(new RefinementState(filters: [
        'name' => new FilterState(value: 'AirPods'),
    ]));

    expect($filters)->count()->toBe(3);
    expect($filters->getFilters()[0]->jsonSerialize())->toMatchArray([
        'is_active' => false,
        'value' => null,
        'default' => 'AirPods',
        'has_default' => true,
        'is_overridden' => true,
        'is_cleared' => true,
    ]);
});

test('request filters preserve JSON scalar values without sentinel coercion', function (mixed $value) {
    $refiner = mock_refiner(
        query: ['filters' => ['value' => ['value' => $value]]],
        refiners: [TextFilter::make('value')],
        apply: false,
    );

    $refiner->applyRefiners();

    expect($refiner->getFilters()[0]->jsonSerialize()['value'])->toBe($value);
})->with([
    'false' => false,
    'decimal' => 2.75,
    'literal null string' => 'null',
]);

test('empty request operators are treated as unspecified', function () {
    $filters = mock_refiner(
        query: ['filters' => ['name' => ['value' => 'AirPods', 'operator' => '']]],
        refiners: [TextFilter::make('name')],
        apply: false,
    );

    $filters->applyRefiners();

    expect($filters->pluck('name')->all())->toBe(['AirPods']);
    expect($filters->getFilters()[0]->jsonSerialize()['operator'])->toBe(Operator::EQUALS->value);
});

test('unsupported request and baseline operators normalize to the filter default', function () {
    $filters = mock_refiner(
        query: ['filters' => ['name' => ['value' => 'AirPods', 'operator' => Operator::GREATER_THAN->value]]],
        refiners: [TextFilter::make('name')],
        apply: false,
    )->withBaseline(new RefinementState(filters: [
        'name' => new FilterState(value: 'AirPods', operator: Operator::GREATER_THAN),
    ]));

    $filters->applyRefiners();

    expect($filters->pluck('name')->all())->toBe(['AirPods']);
    expect($filters->getFilters()[0]->jsonSerialize())->toMatchArray([
        'operator' => Operator::EQUALS->value,
        'default_operator' => Operator::EQUALS->value,
        'is_overridden' => false,
    ]);
});
