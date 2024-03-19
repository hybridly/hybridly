<?php

use Hybridly\View\View;

test('pascal-case component names are converted to kebab-cased ones', function (string $given, string $expected) {
    $view = new View($given, []);

    expect($view->component)->toBe($expected);
})->with([
    ['ShowDashboard', 'show-dashboard'],
    ['dashboard::ShowDashboard', 'dashboard::show-dashboard'],
]);

test('pascal-case component names trigger deprecation warnings', function () {
    set_error_handler(static function (int $errno, string $errstr): never {
        throw new Exception($errstr, $errno);
    }, \E_USER_DEPRECATED);

    new View('dashboard::ShowDashboard', []);

    restore_error_handler();
})->throws('Since hybridly/laravel 0.7: Passing component names with uppercase to Hybridly is deprecated, you should use kebab case instead (Received: dashboard::ShowDashboard, expected: dashboard::show-dashboard)');
