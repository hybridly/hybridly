# Exception handling

## Development

In development, when a non-hybrid response is returned from a hybrid request, it will be displayed in a simple modal.

In other words, Laravel's exception handling keeps working as expected, and the debugging experience is the same as usual.

## Production

In production, it's necessary to extend the exception handler so it returns a valid hybrid response even when an exception has been thrown.

Usually, this consists of returning an `error` page component with the exception's details.

Hybridly makes this fairly simple by providing a `HandlesHybridExceptions` trait. When adding this trait to the exception handler, the `renderHybridResponse` method should be overriden to return the `error` page component.

```php
namespace App\Exceptions;

use Hybridly\Concerns\HandlesHybridExceptions;
use Hybridly\Contracts\HybridResponse;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Handler extends ExceptionHandler
{
    use HandlesHybridExceptions;

    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Returns a hybrid page that renders the exception.
     */
    protected function renderHybridResponse(Response $response, Request $request, \Throwable $e): HybridResponse
    {
        return hybridly('security.error', [
          'status' => $response->getStatusCode()
        ]);
    }
}
```

## Session expiration (419)

By default, when a session expires, Laravel throws a `TokenMismatchException` that renders as an `HTTP 419` code.

When using the `HandlesHybridExceptions` trait, the user will be redirected back to the previous page with a flash message.

This behavior is customizable through the `onSessionExpired` method:

```php
/**
 * Returns a response when the session has expired.
 */
protected function onSessionExpired(Response $response, Request $request, \Throwable $e): mixed
{
    return redirect()->back()->with([
        'error' => 'Your session has expired. Please refresh the page.',
    ]);
}
```

:::info CSRF protection
Though you don't have to set it up, CSRF protection is still enabled in hybrid applications. This is because Axios automatically reads the `XSRF-TOKEN` cookie emitted by Laravel and sends it back in every request.
:::

## Previewing exceptions locally

In a local environment, even when using `HandlesHybridExceptions`, Laravel's exception handler keeps behaving as usual.

To work on `renderHybridResponse`'s implementation or simply preview the error page, you may use the `$skipEnvironments` property. By default, it's set to `local` and `test`, but you may change it to your needs:

```php
class Handler extends ExceptionHandler
{
    use HandlesHybridExceptions;

    /**  // [!code focus:4]
     * List of environments that should not handle Hybridly exceptions.
     */
    protected $skipEnvironments = ['test'];

    /**
     * Returns a hybrid page that renders the exception.
     */
    protected function renderHybridResponse(Response $response, Request $request, \Throwable $e): HybridResponse
    {
        return hybridly('security.error', [
          'status' => $response->getStatusCode()
        ]);
    }
}
```
