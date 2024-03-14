<?php

use Hybridly\Support\VueViewFinder;
use Illuminate\Support\Facades\File;

function with_view_components(array|string $targetPaths, \Closure $assertion): void
{
    File::cleanDirectory(resource_path());

    $paths = collect($targetPaths)->map(function (string $path) {
        return str(resource_path())
            ->finish('/')
            ->append($path)
            ->toString();
    });

    foreach ($paths as $path) {
        File::makeDirectory(dirname($path), recursive: true, force: true);
        File::copy(__DIR__ . '/../../stubs/view.vue', $path);
    }

    $assertion();

    foreach ($paths as $path) {
        File::cleanDirectory(dirname($path));
    }
}

test('`hasView` determines if a view is registered', function (string $target, string $namespace, string $expectedIdentifier) {
    with_view_components($target, function () use ($namespace, $expectedIdentifier) {
        /** @var VueViewFinder */
        $viewFinder = resolve(VueViewFinder::class);
        $viewFinder->loadViewsFrom(
            directory: resource_path('views'),
            namespace: $namespace,
        );

        expect($viewFinder->hasView($expectedIdentifier))->toBeTrue();
    });
})->with([
    ['views/my-view.tsx', 'default', 'my-view'],
    ['views/my-view.vue', 'default', 'my-view'],
    ['views/sub/my-view.vue', 'default', 'sub.my-view'],
    ['views/my-view.vue', 'custom', 'custom::my-view'],
    ['views/sub/my-view.vue', 'custom', 'custom::sub.my-view'],
]);

test('namespaces can be defined as an array and will be converted to kebab case', function () {
    with_view_components('views/my-view.vue', function () {
        /** @var VueViewFinder */
        $viewFinder = resolve(VueViewFinder::class);
        $viewFinder->loadViewsFrom(
            directory: resource_path('views'),
            namespace: ['foo', 'bar'],
        );

        expect($viewFinder->hasView('foo-bar::my-view'))->toBeTrue();
    });
});

test('loading a module recursively only the root `components` and `layouts` directories, but loads all views', function () {
    with_view_components([
        'view1.vue',
        'components/component1.vue',
        'layouts/layout1.vue',
        // level 1
        'subdirectory/view2.vue',
        'subdirectory/components/component2.vue',
        'subdirectory/layouts/layout2.vue',
        // level 2
        'subdirectory/again/view3.vue',
        'subdirectory/again/components/component3.vue',
    ], function () {
        /** @var VueViewFinder */
        $viewFinder = resolve(VueViewFinder::class);
        $viewFinder->loadModuleFrom(
            directory: resource_path(),
            namespace: 'foo',
            deep: true,
        );

        expect($viewFinder->getViews())->toBe([
            ['namespace' => 'foo', 'path' => 'resources/subdirectory/again/view3.vue', 'identifier' => 'foo::subdirectory.again.view3'],
            ['namespace' => 'foo', 'path' => 'resources/subdirectory/view2.vue', 'identifier' => 'foo::subdirectory.view2'],
            ['namespace' => 'foo', 'path' => 'resources/view1.vue', 'identifier' => 'foo::view1'],
        ])->and($viewFinder->getComponents())->toBe([
            ['namespace' => 'foo', 'path' => 'resources/components/component1.vue', 'identifier' => 'foo::component1'],
        ])->and($viewFinder->getLayouts())->toBe([
            ['namespace' => 'foo', 'path' => 'resources/layouts/layout1.vue', 'identifier' => 'foo::layout1'],
        ]);
    });
});

test('changing the base path', function () {
    with_view_components([
        'view1.vue',
        'components/component1.vue',
        'layouts/layout1.vue',
        // level 1
        'subdirectory/view2.vue',
        'subdirectory/components/component2.vue',
        'subdirectory/layouts/layout2.vue',
        // level 2
        'subdirectory/again/view3.vue',
        'subdirectory/again/components/component3.vue',
    ], function () {
        /** @var VueViewFinder */
        $viewFinder = resolve(VueViewFinder::class);
        $viewFinder->loadModuleFrom(
            directory: resource_path(),
            namespace: 'foo',
            deep: true,
        );
        $viewFinder->setBasePath(base_path('resources'));

        expect($viewFinder->getViews())->toBe([
            ['namespace' => 'foo', 'path' => 'subdirectory/again/view3.vue', 'identifier' => 'foo::subdirectory.again.view3'],
            ['namespace' => 'foo', 'path' => 'subdirectory/view2.vue', 'identifier' => 'foo::subdirectory.view2'],
            ['namespace' => 'foo', 'path' => 'view1.vue', 'identifier' => 'foo::view1'],
        ])->and($viewFinder->getComponents())->toBe([
            ['namespace' => 'foo', 'path' => 'components/component1.vue', 'identifier' => 'foo::component1'],
        ])->and($viewFinder->getLayouts())->toBe([
            ['namespace' => 'foo', 'path' => 'layouts/layout1.vue', 'identifier' => 'foo::layout1'],
        ]);
    });
});

test('loading a module non-recursively only loads the root `views`, `components` and `layouts` directories', function () {
    with_view_components([
        // root
        'ignored-view.vue',
        // module
        'views/view1.vue',
        'views/subdirectory/view2.vue',
        'components/component1.vue',
        'components/subdirectory/component2.vue',
        'layouts/layout1.vue',
        'layouts/subdirectory/layout2.vue',
        // level 1 - ignored
        'foo/views/view3.vue',
        'foo/components/component3.vue',
        'foo/layouts/layout3.vue',
    ], function () {
        /** @var VueViewFinder */
        $viewFinder = resolve(VueViewFinder::class);
        $viewFinder->loadModuleFrom(
            directory: resource_path(),
            namespace: 'foo',
            deep: false,
        );

        expect($viewFinder->getViews())->toBe([
            ['namespace' => 'foo', 'path' => 'resources/views/subdirectory/view2.vue', 'identifier' => 'foo::subdirectory.view2'],
            ['namespace' => 'foo', 'path' => 'resources/views/view1.vue', 'identifier' => 'foo::view1'],
        ]);

        expect($viewFinder->getComponents())->toBe([
            ['namespace' => 'foo', 'path' => 'resources/components/component1.vue', 'identifier' => 'foo::component1'],
            ['namespace' => 'foo', 'path' => 'resources/components/subdirectory/component2.vue', 'identifier' => 'foo::subdirectory.component2'],
        ]);

        expect($viewFinder->getLayouts())->toBe([
            ['namespace' => 'foo', 'path' => 'resources/layouts/layout1.vue', 'identifier' => 'foo::layout1'],
            ['namespace' => 'foo', 'path' => 'resources/layouts/subdirectory/layout2.vue', 'identifier' => 'foo::subdirectory.layout2'],
        ]);
    });
});
