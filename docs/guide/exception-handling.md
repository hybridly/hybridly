# Exception handling

## Development

In development, when a non-hybrid response is returned from a hybrid request, it will be displayed in a simple modal.

In other words, Laravel's exception handling keeps working as expected, and the debugging experience is the same as usual.

## Production

In production, it's necessary to extend the exception handler so it returns a valid hybrid response even when an exception has been thrown.

Usually, this consists of returning an `error` view component with the exception's details.

Hybridly makes this fairly simple by providing a `HandleHybridExceptions` class that can be used within the `withExceptions` method. The `renderUsing` method must be given a callback that will return a response when an exception occurs. Usually, a hybrid view should be returned.

:::code-group
```php [bootstrap/app.php]
use Hybridly\Exceptions\HandleHybridExceptions;

return Application::configure(basePath: dirname(__DIR__))
		// ...
    ->withExceptions(
        HandleHybridExceptions::register() // [!code focus:4]
            ->renderUsing(fn (Response $response) => view('error', [
                'status' => $response->getStatusCode()
            ]))
    )
    ->create();
```
:::

:::info Callback arguments
Note that `renderUsing` accepts dependency injection, the `$response`, `$request` and `$exception` named arguments, as well as the `Symfony\Component\HttpFoundation\Response`, `\Illuminate\Http\Request` and `\Throwable` typed arguments.
:::

## Session expiration (419)

By default, when a session expires, Laravel throws a `TokenMismatchException` that renders as an `HTTP 419` code.

When using the `HandleHybridExceptions` class, the user will be redirected back to the previous page with a flash message.

This behavior is customizable through the `expireSessionUsing` method:

:::code-group
```php [bootstrap/app.php]
use Hybridly\Exceptions\HandleHybridExceptions;

return Application::configure(basePath: dirname(__DIR__))
		// ...
    ->withExceptions(
        HandleHybridExceptions::register()
            ->renderUsing(fn (Response $response) => view('error', [
                'status' => $response->getStatusCode()
            ]))
            ->expireSessionUsing(fn () => back()->with([ // [!code focus:3]
                'error' => 'Your session has expired. Please refresh the page.',
            ]))
    )
    ->create();
```
:::

:::info CSRF protection
Though you don't have to set it up, CSRF protection is still enabled in hybrid applications. This is because Axios automatically reads the `XSRF-TOKEN` cookie emitted by Laravel and sends it back in every request.
:::

## Previewing exceptions locally

In a local environment, even when using `HandleHybridExceptions`, Laravel's exception handler keeps behaving as usual.

To work on `renderUsing`'s implementation or simply preview the error page, you may use the `inEnvironments` method. By default, it is set to only `production`.

:::code-group
```php [bootstrap/app.php]
use Hybridly\Exceptions\HandleHybridExceptions;

return Application::configure(basePath: dirname(__DIR__))
		// ...
    ->withExceptions(
        HandleHybridExceptions::register() // [!code focus:5]
            ->inEnvironments('local') // [!code highlight]
            ->renderUsing(fn (Response $response) => view('error', [
                'status' => $response->getStatusCode()
            ]))
    )
    ->create();
```
:::
