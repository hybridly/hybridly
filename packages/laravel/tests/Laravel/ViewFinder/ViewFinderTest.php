<?php

use Hybridly\Architecture\ComponentsResolver;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    /** @var ComponentsResolver */
    $components = resolve(ComponentsResolver::class);
    $components->unload();
});

function with_view_components(array|string $targetPaths, \Closure $assertion): void
{
    File::cleanDirectory(resource_path());

    $paths = collect($targetPaths)
        ->map(function (string $path) {
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
        /** @var ComponentsResolver */
        $components = resolve(ComponentsResolver::class);
        $components->loadViewsFrom(
            directory: resource_path('views'),
            namespace: $namespace,
        );

        expect($components->hasView($expectedIdentifier))->toBeTrue();
    });
})->with([
    ['views/my-view.view.tsx', 'default', 'my-view'],
    ['views/my-view.view.vue', 'default', 'my-view'],
    ['views/sub/my-view.view.vue', 'default', 'sub.my-view'],
    ['views/my-view.view.vue', 'custom', 'custom::my-view'],
    ['views/sub/my-view.view.vue', 'custom', 'custom::sub.my-view'],
]);

test('namespaces can be defined as an array and will be converted to kebab case', function () {
    with_view_components('views/my-view.view.vue', function () {
        /** @var ComponentsResolver */
        $components = resolve(ComponentsResolver::class);
        $components->loadViewsFrom(
            directory: resource_path('views'),
            namespace: ['foo', 'bar'],
        );

        expect($components->hasView('foo-bar::my-view'))->toBeTrue();
    });
});

test('loading a module loads views and layouts recursively', function () {
    with_view_components(
        [
            'component.vue',
            'view1.view.vue',
            'layout1.layout.vue',
            'dir/view2.view.vue',
            'dir/layout2.layout.vue',
        ],
        function () {
            /** @var ComponentsResolver */
            $components = resolve(ComponentsResolver::class);
            $components->loadModuleFrom(
                directory: resource_path(),
                namespace: 'foo',
            );

            expect($components->getViews())
                ->toBe([
                    ['namespace' => 'foo', 'path' => 'resources/view1.view.vue', 'identifier' => 'foo::view1'],
                    ['namespace' => 'foo', 'path' => 'resources/dir/view2.view.vue', 'identifier' => 'foo::dir.view2'],
                ]);

            expect($components->getLayouts())
                ->toBe([
                    ['namespace' => 'foo', 'path' => 'resources/layout1.layout.vue', 'identifier' => 'foo::layout1'],
                    ['namespace' => 'foo', 'path' => 'resources/dir/layout2.layout.vue', 'identifier' => 'foo::dir.layout2'],
                ]);
        },
    );
});

test('identifiers are kebab-cased', function (string $view, string $identifier) {
    with_view_components($view, function () use ($view, $identifier) {
        /** @var ComponentsResolver */
        $components = resolve(ComponentsResolver::class);
        $components->loadModuleFrom(
            directory: resource_path(),
            namespace: 'foo',
        );

        expect($components->getViews())
            ->toBe([
                ['namespace' => 'foo', 'path' => "resources/{$view}", 'identifier' => $identifier],
            ]);
    });
})->with([
    ['MyPascalCaseView.view.vue', 'foo::my-pascal-case-view'],
    ['my-kebab-case-view.view.vue', 'foo::my-kebab-case-view'],
    ['Views/MyPascalCaseView.view.vue', 'foo::views.my-pascal-case-view'],
    ['Views/my-kebab-case-view.view.vue', 'foo::views.my-kebab-case-view'],
    ['views/MyPascalCaseView.view.vue', 'foo::views.my-pascal-case-view'],
    ['views/my-kebab-case-view.view.vue', 'foo::views.my-kebab-case-view'],
]);

test('resolving can use filters', function () {
    with_view_components(
        [
            'ignore-view.vue',
            'index.vue',
        ],
        function () {
            /** @var ComponentsResolver */
            $components = resolve(ComponentsResolver::class);
            $components->loadViewsFrom(
                directory: resource_path(),
                namespace: 'foo',
                filter: fn (string $file) => str_ends_with($file, '.vue') && ! str_contains($file, 'ignore'),
            );

            expect($components->getViews())
                ->toBe([
                    ['namespace' => 'foo', 'path' => 'resources/index.vue', 'identifier' => 'foo::index'],
                ]);
        },
    );
});
