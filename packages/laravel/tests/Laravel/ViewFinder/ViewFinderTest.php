<?php

use Hybridly\Support\VueViewFinder;
use Illuminate\Support\Facades\File;

function with_view_component(string $targetPath, \Closure $assertion): void
{
    $path = str(resource_path())
        ->finish('/')
        ->append($targetPath)
        ->toString();

    File::makeDirectory(dirname($path), recursive: true, force: true);
    File::copy(__DIR__ . '/../../stubs/view.vue', $path);
    $assertion();
    File::cleanDirectory(dirname($path));
}

test('`hasView` determines if a view is registered', function (string $target, string $namespace, string $expectedIdentifier) {
    with_view_component($target, function () use ($namespace, $expectedIdentifier) {
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
    with_view_component('views/my-view.vue', function () {
        /** @var VueViewFinder */
        $viewFinder = resolve(VueViewFinder::class);
        $viewFinder->loadViewsFrom(
            directory: resource_path('views'),
            namespace: ['foo', 'bar'],
        );

        expect($viewFinder->hasView('foo-bar::my-view'))->toBeTrue();
    });
});
