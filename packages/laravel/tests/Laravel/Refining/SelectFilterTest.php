<?php

use Hybridly\Refining\Filters\BaseFilter;
use Hybridly\Refining\Filters\SelectFilter;
use Hybridly\Tests\Fixtures\Database\Product;
use Hybridly\Tests\Fixtures\Database\ProductFactory;
use Hybridly\Tests\Fixtures\Vendor;

it('can be serialized with enum options', function () {
    $filter = SelectFilter::make('vendor')
        ->options(Vendor::class)
        ->metadata(['foo' => 'bar'])
        ->label('Vendor');

    expect($filter)
        ->toBeInstanceOf(BaseFilter::class)
        ->jsonSerialize()
        ->toMatchArray([
            'name' => 'vendor',
            'hidden' => false,
            'label' => 'Vendor',
            'type' => 'select',
            'icon' => null,
            'is_active' => false,
            'value' => null,
            'search_query' => null,
            'default' => null,
        ]);

    $serialized = $filter->jsonSerialize();
    expect($serialized['metadata'])
        ->toHaveKey('foo', 'bar')
        ->toHaveKey('options');
});

it('filters using enum options', function () {
    ProductFactory::new()->create(['vendor' => Vendor::Apple]);
    ProductFactory::new()->create(['vendor' => Vendor::Microsoft]);

    $filters = mock_refiner(
        query: ['filters' => ['vendor' => ['value' => 'microsoft']]],
        refiners: [
            SelectFilter::make('vendor')->options(Vendor::class),
        ],
    );

    expect($filters)
        ->count()
        ->toBe(1)
        ->first()
        ->vendor->toBe(Vendor::Microsoft);
});

it('supports custom operator with enum options', function () {
    ProductFactory::new()->create(['vendor' => Vendor::Apple]);
    ProductFactory::new()->create(['vendor' => Vendor::Microsoft]);

    $filters = mock_refiner(
        query: ['filters' => ['vendor' => ['value' => 'microsoft', 'operator' => 'not_equals']]],
        refiners: [
            SelectFilter::make('vendor')
                ->options(Vendor::class),
        ],
    );

    expect($filters)
        ->count()
        ->toBe(1)
        ->first()
        ->vendor->toBe(Vendor::Apple);
});

it('supports multiple selection with enum options', function () {
    ProductFactory::new()->create(['vendor' => Vendor::Apple]);
    ProductFactory::new()->create(['vendor' => Vendor::Microsoft]);

    $filters = mock_refiner(
        query: ['filters' => ['vendor' => ['value' => ['apple', 'microsoft']]]],
        refiners: [
            SelectFilter::make('vendor')
                ->options(Vendor::class)
                ->multiple(),
        ],
    );

    expect($filters)->count()->toBe(2);
});

it('validates enum values and ignores invalid ones', function () {
    ProductFactory::new()->create(['vendor' => Vendor::Apple]);
    ProductFactory::new()->create(['vendor' => Vendor::Microsoft]);

    $filters = mock_refiner(
        query: ['filters' => ['vendor' => ['value' => 'invalid']]],
        refiners: [
            SelectFilter::make('vendor')->options(Vendor::class),
        ],
    );

    expect($filters)->count()->toBe(0);
});

it('validates multiple enum values and ignores invalid ones', function () {
    ProductFactory::new()->create(['vendor' => Vendor::Apple]);
    ProductFactory::new()->create(['vendor' => Vendor::Microsoft]);

    $filters = mock_refiner(
        query: ['filters' => ['vendor' => ['value' => ['apple', 'invalid']]]],
        refiners: [
            SelectFilter::make('vendor')
                ->options(Vendor::class)
                ->multiple(),
        ],
    );

    expect($filters)->count()->toBe(1);
});

it('supports using query builder as options', function () {
    $product1 = ProductFactory::new()->create(['name' => 'iPhone', 'vendor' => Vendor::Apple]);
    $product2 = ProductFactory::new()->create(['name' => 'MacBook', 'vendor' => Vendor::Apple]);
    $product3 = ProductFactory::new()->create(['name' => 'Surface', 'vendor' => Vendor::Microsoft]);

    $filters = mock_refiner(
        query: ['filters' => ['id' => ['value' => (string) $product1->id]]],
        refiners: [
            SelectFilter::make('id')
                ->options(Product::query()->where('vendor', Vendor::Apple))
                ->formatOptionLabelUsing(fn (Product $product) => $product->name),
        ],
    );

    expect($filters)
        ->count()
        ->toBe(1)
        ->first()
        ->id->toBe($product1->id);
});

it('supports using model class as options', function () {
    $product1 = ProductFactory::new()->create(['name' => 'iPhone']);
    $product2 = ProductFactory::new()->create(['name' => 'MacBook']);

    $filters = mock_refiner(
        query: ['filters' => ['id' => ['value' => (string) $product1->id]]],
        refiners: [
            SelectFilter::make('id')
                ->options(Product::class)
                ->formatOptionLabelUsing(fn (Product $product) => $product->name),
        ],
    );

    expect($filters)
        ->count()
        ->toBe(1)
        ->first()
        ->id->toBe($product1->id);
});

it('supports multiple selection with builder options', function () {
    $product1 = ProductFactory::new()->create(['name' => 'iPhone']);
    $product2 = ProductFactory::new()->create(['name' => 'MacBook']);
    $product3 = ProductFactory::new()->create(['name' => 'Surface']);

    $filters = mock_refiner(
        query: ['filters' => ['id' => ['value' => [(string) $product1->id, (string) $product2->id]]]],
        refiners: [
            SelectFilter::make('id')
                ->options(Product::class)
                ->formatOptionLabelUsing(fn (Product $product) => $product->name)
                ->multiple(),
        ],
    );

    expect($filters)->count()->toBe(2);
});

it('can hide options from metadata', function () {
    $filter = SelectFilter::make('vendor')
        ->options(Vendor::class)
        ->withoutProvidingOptions();

    $metadata = $filter->jsonSerialize()['metadata'];

    expect($metadata)->not->toHaveKey('options');
});

it('provides selected options label for single selection', function () {
    ProductFactory::new()->create(['vendor' => Vendor::Apple]);

    $refiner = mock_refiner(
        query: ['filters' => ['vendor' => ['value' => 'apple']]],
        refiners: [
            SelectFilter::make('vendor')->options(Vendor::class),
        ],
        apply: true,
    );

    $filter = $refiner->getFilters()[0];
    $metadata = $filter->jsonSerialize()['metadata'];

    expect($metadata['selected_options_label'])->toBe('Apple');
});

it('provides selected options label for multiple selection', function () {
    ProductFactory::new()->create(['vendor' => Vendor::Apple]);
    ProductFactory::new()->create(['vendor' => Vendor::Microsoft]);

    $refiner = mock_refiner(
        query: ['filters' => ['vendor' => ['value' => ['apple', 'microsoft']]]],
        refiners: [
            SelectFilter::make('vendor')
                ->options(Vendor::class)
                ->multiple(),
        ],
        apply: true,
    );

    $filter = $refiner->getFilters()[0];
    $metadata = $filter->jsonSerialize()['metadata'];

    expect($metadata['selected_options_label'])->toBe('2 vendors');
});

it('supports custom selected options label', function () {
    ProductFactory::new()->create(['vendor' => Vendor::Apple]);
    ProductFactory::new()->create(['vendor' => Vendor::Microsoft]);

    $refiner = mock_refiner(
        query: ['filters' => ['vendor' => ['value' => ['apple', 'microsoft']]]],
        refiners: [
            SelectFilter::make('vendor')
                ->options(Vendor::class)
                ->multiple()
                ->selectedOptionsLabel('supplier'),
        ],
        apply: true,
    );

    $filter = $refiner->getFilters()[0];
    $metadata = $filter->jsonSerialize()['metadata'];

    expect($metadata['selected_options_label'])->toBe('2 suppliers');
});

it('supports custom option label formatting', function () {
    $filter = SelectFilter::make('vendor')
        ->options(Vendor::class)
        ->formatOptionLabelUsing(fn ($option) => strtoupper($option->value));

    $metadata = $filter->jsonSerialize()['metadata'];

    expect($metadata['options'])
        ->toBe([
            'apple' => 'APPLE',
            'microsoft' => 'MICROSOFT',
        ]);
});

it('marks filter as searchable when searchable is enabled', function () {
    $filter = SelectFilter::make('id')
        ->options(Product::class)
        ->searchable('name');

    $metadata = $filter->jsonSerialize()['metadata'];

    expect($metadata['is_searchable'])->toBeTrue();
});

it('is not searchable by default', function () {
    $filter = SelectFilter::make('vendor')->options(Vendor::class);

    $metadata = $filter->jsonSerialize()['metadata'];

    expect($metadata)->not->toHaveKey('is_searchable');
});
