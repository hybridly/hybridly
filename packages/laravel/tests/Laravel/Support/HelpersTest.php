<?php

use Hybridly\Hybridly;
use Hybridly\Support\Deferred;
use Hybridly\Support\Header;
use Hybridly\Support\MissingViewComponentException;
use Hybridly\Support\Partial;
use Hybridly\View\Factory;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

use function Hybridly\{deferred, dialog, is_hybrid, is_partial, partial, properties, to_external_url, view};

describe('namespaced', function () {
    beforeEach(function () {
        app()->forgetInstance(Factory::class);
    });

    test('`view` returns a view', function () {
        expect(view('users.index', ['foo' => 'bar']))
            ->toBeInstanceOf(Factory::class)
            ->render()
            ->toMatchSnapshot();
    });

    test('`dialog` returns a dialog and its base view', function () {
        Route::get('/', fn () => view('users.index'))->name('users.index');

        mock_request(
            hybridly: false,
            headers: [Header::DIALOG_KEY => 'foo-bar'],
            bind: true,
        );

        expect(dialog('users.show', ['user' => 'Jon Doe'], route('users.index')))
            ->toBeInstanceOf(Factory::class)
            ->render()
            ->toMatchSnapshot();
    });

    test('`properties` throws when there is no view', function () {
        properties(['foo' => 'bar'])->render();
    })->throws(MissingViewComponentException::class);

    test('`properties` returns properties only', function () {
        mock_request(hybridly: true, bind: true);

        expect(properties(['foo' => 'bar']))
            ->toBeInstanceOf(Factory::class)
            ->render();
    });

    test('`partial` returns a `Partial` instance', function () {
        expect(partial(fn () => 'foo'))
            ->toBeInstanceOf(Partial::class)
            ->__invoke()->toBe('foo');
    });

    test('`deferred` returns a `Deferred` instance', function () {
        expect(deferred(fn () => 'bar'))
            ->toBeInstanceOf(Deferred::class)
            ->__invoke()->toBe('bar');
    });

    test('`to_external_url` returns a `RedirectResponse` on non-hybrid requests', function () {
        expect(to_external_url('https://google.fr'))
            ->toBeInstanceOf(RedirectResponse::class)
            ->getTargetUrl()->toBe('https://google.fr');
    });

    test('`to_external_url` accepts `RedirectResponse` as a parameter', function () {
        expect(to_external_url(new RedirectResponse('https://google.fr')))
            ->toBeInstanceOf(RedirectResponse::class)
            ->getTargetUrl()->toBe('https://google.fr');
    });

    test('`to_external_url` returns a `HTTP_CONFLICT` response on hybrid requests', function () {
        mock_request(hybridly: true, bind: true);

        expect(to_external_url('https://google.fr'))
            ->toBeInstanceOf(Response::class)
            ->getStatusCode()->toBe(Response::HTTP_CONFLICT);
    });

    test('`is_hybrid` determines whether a request is hybrid', function (bool $expected) {
        mock_request(hybridly: $expected, bind: true);
        expect(is_hybrid())->toBe($expected);
    })->with([true, false]);

    test('`is_partial` returns `false` on non-hybrid requests', function () {
        expect(is_partial())->toBe(false);
    });

    test('`is_partial` returns `true` on hybrid requests', function () {
        mock_request(
            hybridly: true,
            headers: [Header::PARTIAL_COMPONENT => 'foo'],
            bind: true,
        );

        expect(is_partial())->toBeTrue();
    });
});

describe('global', function () {
    test('`hybridly` returns a view when called with parameters', function () {
        expect(hybridly('foo.bar'))
            ->toBeInstanceOf(Factory::class)
            ->render()
            ->toMatchSnapshot();
    });

    test('`hybridly` returns the singleton when called with no parameters', function () {
        expect(hybridly())->toBeInstanceOf(Hybridly::class);
    });
});
