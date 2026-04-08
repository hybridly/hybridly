# Exception handling

<p class="preface">
Learn how debug in development and how to customize the error pages for a better user experience.
</p>

## Overview

During development, when a non-hybrid response is returned from a hybrid request, it will be displayed in a modal. The navigation will be cancelled, and the modal will be dismissible.

In other words, Laravel's exception handling keeps working as expected, and the debugging experience is the same as usual.

## Production

By default, in production, exceptions have the same behavior. Presenting an error modal with no information is not particularly a good user experience, so you will probably want to handle them and return a proper response page.

Hybridly makes this fairly simple by providing a `renderExceptionsUsing` method on the `Hybridly` instance. It must be given a callback that is responsible for returning a response, which is usually a hybrid view.

Typically, this is done in a service provider:

:::code-group

```php [app/Providers/AppServiceProvider.php]
use Hybridly\Hybridly;

public function boot(Hybridly $hybridly): void
{
    $hybridly->renderExceptionsUsing(fn (Response $response) => view('error', [
        'status' => $response->getStatusCode()
    ]));
}
```

:::

## Session expiration

By default, when a session expires, Laravel throws a `TokenMismatchException` that renders as an `HTTP 419` code.

You can catch these exceptions by using the `handleSessionExpirationUsing` method on the `Hybridly` instance.

```php [app/Providers/AppServiceProvider.php]
use Hybridly\Hybridly;

public function boot(Hybridly $hybridly): void
{
    $hybridly->handleSessionExpirationUsing(fn () => back()->with([
        'error' => 'Your session has expired. Please refresh the page.',
    ]));
}
```

## Previewing exceptions locally

When working on your exception page, you might not want to receive the default error modal that Hybridly uses during development.

You can instruct Hybridly to render your exception defined using `renderExceptionsUsing` by using `renderExceptionsInDevelopment`:

:::code-group

```php [app/Providers/AppServiceProvider.php]
use Hybridly\Hybridly;

public function boot(Hybridly $hybridly): void
{
    $hybridly->renderExceptionsInDevelopment(); // [!code hl]
    $hybridly->renderExceptionsUsing(fn (Response $response) => view('error', [
        'status' => $response->getStatusCode()
    ]));
}
```

:::
