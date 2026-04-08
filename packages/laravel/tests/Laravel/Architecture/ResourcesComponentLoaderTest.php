<?php

use Hybridly\Architecture\ComponentType;
use Hybridly\Architecture\ResourcesComponentLoader;

beforeEach(function () {
    File::ensureDirectoryExists(resource_path());
    File::cleanDirectory(resource_path());
});

test('resources component loader discovers views and layouts recursively', function () {
    with_components(
        [
            'users/EditProfile.view.vue',
            'users/settings/MainLayout.layout.tsx',
            'plain.view.tsx',
            'ignored.ts',
        ],
        function () {
            $components = (new ResourcesComponentLoader())->load();

            expect($components[0])->identifier->toBe('plain');
            expect($components[0])->type->toBe(ComponentType::VIEW);
            expect($components[0])->path->toBe('resources/plain.view.tsx');

            expect($components[1])->identifier->toBe('users.edit-profile');
            expect($components[1])->type->toBe(ComponentType::VIEW);
            expect($components[1])->path->toBe('resources/users/EditProfile.view.vue');

            expect($components[2])->identifier->toBe('users.settings.main-layout');
            expect($components[2])->type->toBe(ComponentType::LAYOUT);
            expect($components[2])->path->toBe('resources/users/settings/MainLayout.layout.tsx');
        },
    );
});
