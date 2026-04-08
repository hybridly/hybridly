<?php

use Hybridly\Architecture\Component;
use Hybridly\Architecture\ComponentLoader;
use Hybridly\Architecture\ComponentType;
use Hybridly\Architecture\JustInTimeComponentRepository;
use Hybridly\Architecture\ResourcesComponentLoader;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    File::ensureDirectoryExists(resource_path());
    File::cleanDirectory(resource_path());
});

test('just-in-time component repository lists and resolves components by identifier', function () {
    with_components(
        [
            'dashboard/index.view.vue',
            'dashboard/default.layout.vue',
        ],
        function () {
            $repository = new JustInTimeComponentRepository(new ResourcesComponentLoader());
            $views = array_values($repository->list(ComponentType::VIEW));
            $layouts = array_values($repository->list(ComponentType::LAYOUT));

            expect($views)
                ->toHaveCount(1)
                ->and($views[0]->identifier)
                ->toBe('dashboard.index');

            expect($layouts)
                ->toHaveCount(1)
                ->and($layouts[0]->identifier)
                ->toBe('dashboard.default');

            expect($repository->has('dashboard.index'))->toBeTrue();
            expect($repository->has('dashboard.default'))->toBeTrue();
            expect($repository->has('missing.component'))->toBeFalse();
            expect($repository->has(new Component(ComponentType::VIEW, 'path', 'dashboard.index')))->toBeTrue();
        },
    );
});

test('just-in-time component repository resolves components only once', function () {
    $loader = new class() implements ComponentLoader {
        public int $calls = 0;

        public function load(): array
        {
            $this->calls++;

            return [
                new Component(type: ComponentType::VIEW, path: 'resources/index.view.vue', identifier: 'index'),
                new Component(type: ComponentType::LAYOUT, path: 'resources/default.layout.vue', identifier: 'default'),
            ];
        }
    };

    $repository = new JustInTimeComponentRepository($loader);

    expect($loader->calls)->toBe(0);
    expect($repository->has('index'))->toBeTrue();
    expect($repository->list(ComponentType::VIEW))->toHaveCount(1);
    expect($repository->list(ComponentType::LAYOUT))->toHaveCount(1);
    expect($loader->calls)->toBe(1);
});
