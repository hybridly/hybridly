<?php

use Hybridly\Support\Configuration\Configuration;
use Hybridly\Support\RouteExtractor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Laravel\Folio\Folio;
use Laravel\Folio\FolioServiceProvider;

beforeEach(function () {
    if ((int) head(explode('.', app()->version())) < 10) {
        return $this->markTestSkipped('Folio requires Laravel >=10');
    }

    app()->register(FolioServiceProvider::class);

    Configuration::get()->router->excludedRoutes = [
        Configuration::get()->tables->actionsEndpoint,
    ];

    app()->setBasePath(str_replace('/vendor/orchestra/testbench-core/laravel', '', app()->basePath()));
});

afterEach(function () {
    File::deleteDirectories(resource_path('views'));
});

test('include named folio routes', function () {
    File::ensureDirectoryExists(resource_path('views/pages'));
    File::put(resource_path('views/pages/about.blade.php'), '<?php Laravel\Folio\name("about");');
    File::put(resource_path('views/pages/anonymous.blade.php'), '<?php');
    File::ensureDirectoryExists(resource_path('views/pages/users'));
    File::put(resource_path('views/pages/users/[id].blade.php'), '<?php Laravel\Folio\name("users.show");');

    Folio::path(resource_path('views/pages'));

    expect(app(RouteExtractor::class)->getRoutes())->toEqualCanonicalizing([
        'about' => [
            'uri' => 'about',
            'method' => ['GET'],
            'domain' => null,
            'name' => 'about',
            'parameters' => [],
            'bindings' => [],
            'wheres' => [],
        ],
        'users.show' => [
            'uri' => 'users/{id}',
            'method' => ['GET'],
            'domain' => null,
            'name' => 'users.show',
            'parameters' => ['id'],
            'bindings' => [],
            'wheres' => [],
        ],
    ]);
});

test('normal routes override folio routes', function () {
    Route::get('about', fn () => '')->name('about');

    File::ensureDirectoryExists(resource_path('views/pages'));
    File::put(resource_path('views/pages/about.blade.php'), '<?php Laravel\Folio\name("about");');

    Folio::path(resource_path('views/pages'));

    expect(Arr::except(app(RouteExtractor::class)->getRoutes(), 'laravel-folio'))->toBe([
        'about' => [
            'uri' => 'about',
            // Folio routes only respond to 'GET', so this has to be the web route
            'method' => ['GET'],
            'domain' => null,
            'name' => 'about',
            'parameters' => [],
            'bindings' => [],
            'wheres' => [],
        ],
    ]);
});

test('parameters', function () {
    File::ensureDirectoryExists(resource_path('views/pages/users'));
    File::put(resource_path('views/pages/users/[id].blade.php'), '<?php Laravel\Folio\name("users.show");');
    File::put(resource_path('views/pages/users/[...ids].blade.php'), '<?php Laravel\Folio\name("users.some");');

    Folio::path(resource_path('views/pages'));

    expect(app(RouteExtractor::class)->getRoutes()['users.show'])->toBe([
        'uri' => 'users/{id}',
        'method' => ['GET'],
        'domain' => null,
        'name' => 'users.show',
        'parameters' => ['id'],
        'bindings' => [],
        'wheres' => [],
    ]);
    expect(app(RouteExtractor::class)->getRoutes()['users.some'])->toBe([
        'uri' => 'users/{ids}',
        'method' => ['GET'],
        'domain' => null,
        'name' => 'users.some',
        'parameters' => ['ids'],
        'bindings' => [],
        'wheres' => [],
    ]);
});

test('domains', function () {
    File::ensureDirectoryExists(resource_path('views/pages/admin'));
    File::put(resource_path('views/pages/admin/[...ids].blade.php'), '<?php Laravel\Folio\name("admins.some");');

    Folio::domain('{account}.{org}.ziggy.dev')->path(resource_path('views/pages/admin'))->uri('admin');

    expect(Arr::except(app(RouteExtractor::class)->getRoutes(), 'laravel-folio'))->toBe([
        'admins.some' => [
            'uri' => 'admin/{ids}',
            'method' => ['GET'],
            'domain' => '{account}.{org}.ziggy.dev',
            'name' => 'admins.some',
            'parameters' => ['account', 'org', 'ids'],
            'bindings' => [],
            'wheres' => [],
        ],
    ]);
});

test('paths and uris', function () {
    File::ensureDirectoryExists(resource_path('views/pages/guest'));
    File::ensureDirectoryExists(resource_path('views/pages/admin'));
    File::put(resource_path('views/pages/guest/[id].blade.php'), '<?php Laravel\Folio\name("guests.show");');
    File::put(resource_path('views/pages/admin/[...ids].blade.php'), '<?php Laravel\Folio\name("admins.some");');

    Folio::path(resource_path('views/pages/guest'))->uri('/');
    Folio::path(resource_path('views/pages/admin'))->uri('/admin');

    expect(Arr::except(app(RouteExtractor::class)->getRoutes(), 'laravel-folio'))->toBe([
        'guests.show' => [
            'uri' => '{id}',
            'method' => ['GET'],
            'domain' => null,
            'name' => 'guests.show',
            'parameters' => ['id'],
            'bindings' => [],
            'wheres' => [],
        ],
        'admins.some' => [
            'uri' => 'admin/{ids}',
            'method' => ['GET'],
            'domain' => null,
            'name' => 'admins.some',
            'parameters' => ['ids'],
            'bindings' => [],
            'wheres' => [],
        ],
    ]);
});

test('index pages', function () {
    File::ensureDirectoryExists(resource_path('views/pages/index/index'));
    File::put(resource_path('views/pages/index.blade.php'), '<?php Laravel\Folio\name("root");');
    File::put(resource_path('views/pages/index/index/index.blade.php'), '<?php Laravel\Folio\name("index.index");');

    Folio::path(resource_path('views/pages'));

    expect(app(RouteExtractor::class)->getRoutes()['root'])->toBe([
        'uri' => '/',
        'method' => ['GET'],
        'domain' => null,
        'name' => 'root',
        'parameters' => [],
        'bindings' => [],
        'wheres' => [],
    ]);
    expect(app(RouteExtractor::class)->getRoutes()['index.index'])->toBe([
        'uri' => 'index/index',
        'method' => ['GET'],
        'domain' => null,
        'name' => 'index.index',
        'parameters' => [],
        'bindings' => [],
        'wheres' => [],
    ]);
});

test('nested pages', function () {
    File::ensureDirectoryExists(resource_path('views/pages/[slug]'));
    File::put(resource_path('views/pages/[slug]/[id].blade.php'), '<?php Laravel\Folio\name("nested");');

    Folio::path(resource_path('views/pages'));

    expect(Arr::except(app(RouteExtractor::class)->getRoutes(), 'laravel-folio'))->toBe([
        'nested' => [
            'uri' => '{slug}/{id}',
            'method' => ['GET'],
            'domain' => null,
            'name' => 'nested',
            'parameters' => ['slug', 'id'],
            'bindings' => [],
            'wheres' => [],
        ],
    ]);
});

test('custom view data variable name', function () {
    File::ensureDirectoryExists(resource_path('views/pages/users/[.App.User-$leader]/users'));
    File::put(resource_path('views/pages/users/[.App.User-$leader]/users/[.App.User-$follower].blade.php'), '<?php Laravel\Folio\name("follower");');
    if (!windows_os()) {
        File::ensureDirectoryExists(resource_path('views/pages/linux-users/[.App.User|leader]/users'));
        File::put(resource_path('views/pages/linux-users/[.App.User|leader]/users/[.App.User|follower].blade.php'), '<?php Laravel\Folio\name("linux.follower");');
    }

    Folio::path(resource_path('views/pages'));

    expect(app(RouteExtractor::class)->getRoutes()['follower'])->toBe([
        'uri' => 'users/{leader}/users/{follower}',
        'method' => ['GET'],
        'domain' => null,
        'name' => 'follower',
        'parameters' => ['leader', 'follower'],
        'bindings' => [],
        'wheres' => [],
    ]);
    if (!windows_os()) {
        expect(app(RouteExtractor::class)->getRoutes()['linux.follower'])->toBe([
            'uri' => 'linux-users/{leader}/users/{follower}',
            'method' => ['GET'],
            'domain' => null,
            'name' => 'linux.follower',
            'parameters' => ['leader', 'follower'],
            'bindings' => [],
            'wheres' => [],
        ]);
    }
});

test('custom route model binding field', function () {
    File::ensureDirectoryExists(resource_path('views/pages/things'));
    File::put(resource_path('views/pages/things/[Thing].blade.php'), '<?php Laravel\Folio\name("things.show");');
    if (!windows_os()) {
        File::ensureDirectoryExists(resource_path('views/pages/posts'));
        File::put(resource_path('views/pages/posts/[Post:slug].blade.php'), '<?php Laravel\Folio\name("posts.show");');
    }
    File::ensureDirectoryExists(resource_path('views/pages/teams'));
    File::put(resource_path('views/pages/teams/[Team-uid].blade.php'), '<?php Laravel\Folio\name("teams.show");');

    Folio::path(resource_path('views/pages'));

    if (!windows_os()) {
        expect(app(RouteExtractor::class)->getRoutes()['posts.show'])->toBe([
            'uri' => 'posts/{post}',
            'method' => ['GET'],
            'domain' => null,
            'name' => 'posts.show',
            'parameters' => ['post'],
            'bindings' => [
                'post' => 'slug',
            ],
            'wheres' => [],
        ]);
    }
    expect(app(RouteExtractor::class)->getRoutes()['things.show'])->toBe([
        'uri' => 'things/{thing}',
        'method' => ['GET'],
        'domain' => null,
        'name' => 'things.show',
        'parameters' => ['thing'],
        'bindings' => [],
        'wheres' => [],
    ]);
    expect(app(RouteExtractor::class)->getRoutes()['teams.show'])->toBe([
        'uri' => 'teams/{team}',
        'method' => ['GET'],
        'domain' => null,
        'name' => 'teams.show',
        'parameters' => ['team'],
        'bindings' => [
            'team' => 'uid',
        ],
        'wheres' => [],
    ]);
});

test('custom model paths', function () {
    File::ensureDirectoryExists(resource_path('views/pages/users'));
    File::put(resource_path('views/pages/users/[.App.User].blade.php'), '<?php Laravel\Folio\name("users.show");');
    if (!windows_os()) {
        File::ensureDirectoryExists(resource_path('views/pages/posts'));
        File::put(resource_path('views/pages/posts/[.App.Post:slug].blade.php'), '<?php Laravel\Folio\name("posts.show");');
    }
    File::ensureDirectoryExists(resource_path('views/pages/teams'));
    File::put(resource_path('views/pages/teams/[.App.Team-uid].blade.php'), '<?php Laravel\Folio\name("teams.show");');

    Folio::path(resource_path('views/pages'));

    if (!windows_os()) {
        expect(app(RouteExtractor::class)->getRoutes()['posts.show'])->toBe([
            'uri' => 'posts/{post}',
            'method' => ['GET'],
            'domain' => null,
            'name' => 'posts.show',
            'parameters' => ['post'],
            'bindings' => [
                'post' => 'slug',
            ],
            'wheres' => [],
        ]);
    }
    expect(app(RouteExtractor::class)->getRoutes()['users.show'])->toBe([
        'uri' => 'users/{user}',
        'method' => ['GET'],
        'domain' => null,
        'name' => 'users.show',
        'parameters' => ['user'],
        'bindings' => [],
        'wheres' => [],
    ]);
    expect(app(RouteExtractor::class)->getRoutes()['teams.show'])->toBe([
        'uri' => 'teams/{team}',
        'method' => ['GET'],
        'domain' => null,
        'name' => 'teams.show',
        'parameters' => ['team'],
        'bindings' => [
            'team' => 'uid',
        ],
        'wheres' => [],
    ]);
});

test('implicit route model binding', function () {
    File::ensureDirectoryExists(resource_path('views/pages/users'));
    File::put(resource_path('views/pages/users/[FolioUser].blade.php'), '<?php Laravel\Folio\name("users.show");');
    File::ensureDirectoryExists(resource_path('views/pages/tags'));
    File::put(resource_path('views/pages/tags/[FolioTag].blade.php'), '<?php Laravel\Folio\name("tags.show");');

    Folio::path(resource_path('views/pages'));

    expect(app(RouteExtractor::class)->getRoutes()['users.show'])->toBe([
        'uri' => 'users/{folioUser}',
        'method' => ['GET'],
        'domain' => null,
        'name' => 'users.show',
        'parameters' => ['folioUser'],
        'bindings' => [
            'folioUser' => 'uuid',
        ],
        'wheres' => [],
    ]);
    expect(app(RouteExtractor::class)->getRoutes()['tags.show'])->toBe([
        'uri' => 'tags/{folioTag}',
        'method' => ['GET'],
        'domain' => null,
        'name' => 'tags.show',
        'parameters' => ['folioTag'],
        'bindings' => [
            'folioTag' => 'id',
        ],
        'wheres' => [],
    ]);

    expect(FolioUser::$wasBooted)->toBeTrue();
    expect(FolioTag::$wasBooted)->toBeFalse();
});

test('implicit route model binding and custom view data variable name', function () {
    File::ensureDirectoryExists(resource_path('views/pages/users'));
    File::put(resource_path('views/pages/users/[FolioUser-$user].blade.php'), '<?php Laravel\Folio\name("users.show");');
    if (!windows_os()) {
        File::ensureDirectoryExists(resource_path('views/pages/tags'));
        File::put(resource_path('views/pages/tags/[FolioTag|tag].blade.php'), '<?php Laravel\Folio\name("tags.show");');
    }

    Folio::path(resource_path('views/pages'));

    expect(app(RouteExtractor::class)->getRoutes()['users.show'])->toBe([
        'uri' => 'users/{user}',
        'method' => ['GET'],
        'domain' => null,
        'name' => 'users.show',
        'parameters' => ['user'],
        'bindings' => [
            'user' => 'uuid',
        ],
        'wheres' => [],
    ]);
    if (!windows_os()) {
        expect(app(RouteExtractor::class)->getRoutes()['tags.show'])->toBe([
            'uri' => 'tags/{tag}',
            'method' => ['GET'],
            'domain' => null,
            'name' => 'tags.show',
            'parameters' => ['tag'],
            'bindings' => [
                'tag' => 'id',
            ],
            'wheres' => [],
        ]);
    }
});

test('custom route model binding field and custom view data variable name', function () {
    if (!windows_os()) {
        File::ensureDirectoryExists(resource_path('views/pages/users'));
        File::put(resource_path('views/pages/users/[FolioUser:email|user].blade.php'), '<?php Laravel\Folio\name("users.show");');
    }
    File::ensureDirectoryExists(resource_path('views/pages/tags'));
    File::put(resource_path('views/pages/tags/[FolioTag-slug-$tag].blade.php'), '<?php Laravel\Folio\name("tags.show");');

    Folio::path(resource_path('views/pages'));

    if (!windows_os()) {
        expect(app(RouteExtractor::class)->getRoutes()['users.show'])->toBe([
            'uri' => 'users/{user}',
            'method' => ['GET'],
            'domain' => null,
            'name' => 'users.show',
            'parameters' => ['user'],
            'bindings' => [
                'user' => 'email',
            ],
            'wheres' => [],
        ]);
    }
    expect(app(RouteExtractor::class)->getRoutes()['tags.show'])->toBe([
        'uri' => 'tags/{tag}',
        'method' => ['GET'],
        'domain' => null,
        'name' => 'tags.show',
        'parameters' => ['tag'],
        'bindings' => [
            'tag' => 'slug',
        ],
        'wheres' => [],
    ]);
});

class FolioUser extends Model
{
    public static $wasBooted = false;

    public static function boot()
    {
        parent::boot();
        static::$wasBooted = true;
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }
}

class FolioTag extends Model
{
    public static $wasBooted = false;

    public static function boot()
    {
        parent::boot();
        static::$wasBooted = true;
    }
}
