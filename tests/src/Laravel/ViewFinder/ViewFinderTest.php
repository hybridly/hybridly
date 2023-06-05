<?php

use Hybridly\ViewFinder\VueViewFinder;
use Illuminate\Support\Facades\File;

function with_page_component(string $targetPath, \Closure $assertion): void
{
    $path = str(resource_path())
        ->finish('/')
        ->append($targetPath)
        ->toString();

    File::makeDirectory(dirname($path), recursive: true, force: true);
    File::copy(__DIR__ . '/../../stubs/page.vue', $path);
    $assertion();
    File::cleanDirectory(dirname($path));
}

test('`hasView` determines if a view is registered', function (string $target, string $namespace, string $expectedIdentifier) {
    with_page_component($target, function () use ($namespace, $expectedIdentifier) {
        /** @var VueViewFinder */
        $viewFinder = resolve(VueViewFinder::class);
        $viewFinder->loadViewsFrom(
            directory: resource_path('pages'),
            namespace: $namespace,
        );

        expect($viewFinder->hasView($expectedIdentifier))->toBeTrue();
    });
})->with([
    ['pages/my-page.tsx', 'default', 'my-page'],
    ['pages/my-page.vue', 'default', 'my-page'],
    ['pages/sub/my-page.vue', 'default', 'sub.my-page'],
    ['pages/my-page.vue', 'custom', 'custom::my-page'],
    ['pages/sub/my-page.vue', 'custom', 'custom::sub.my-page'],
]);
