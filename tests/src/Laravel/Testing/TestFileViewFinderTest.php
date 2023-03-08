<?php

use Hybridly\Testing\TestFileViewFinder;
use Illuminate\Support\Facades\File;

it('finds normal pages in the views directory', function () {
    /** @var TestFileViewFinder */
    $finder = resolve('hybridly.testing.view_finder');
    $pages_directory = "pages";
    $page_path = "pages/my-page.vue";

    File::makeDirectory(resource_path($pages_directory), recursive: true, force: true);
    File::copy(__DIR__ . '/../../stubs/my-page.vue', resource_path($page_path));

    expect($finder->find("my-page"))->toEndWith($page_path);
});

it('finds domain-based pages', function () {
    /** @var TestFileViewFinder */
    $finder = resolve('hybridly.testing.view_finder');
    $domain = 'my-domain';
    $pages_directory = "domains/{$domain}/pages";
    $page_path = "domains/{$domain}/pages/my-page.vue";

    File::makeDirectory(resource_path($pages_directory), recursive: true, force: true);
    File::copy(__DIR__ . '/../../stubs/my-page.vue', resource_path($page_path));

    expect($finder->find("{$domain}:my-page"))->toEndWith($page_path);
});
