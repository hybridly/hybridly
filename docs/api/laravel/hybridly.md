# Hybridly

<p class="preface">
This is a singleton instance that contains convenience methods for common actions. Most response-oriented methods are shortcuts to <a href="./functions.md">namespaced functions</a>.

</p>

## `view`

Returns a `Hybridly\View\Factory` for the given component and optional properties.

- See [responses](../../guide/responses.md) for more details.
- For dialogs, use the [`dialog` namespaced function](./functions.md#dialog).

### Usage

```php
return $this->hybridly->view('users.show', [
    'user' => UserData::from($user),
]);
```

## `properties`

Returns updated properties for the current view.

- See [responses](../../guide/responses.md#updating-properties) for more details.

### Usage

```php
return $this->hybridly->properties([
    'user' => UserData::from($user),
]);
```

## `createExternalRedirect`

Generates a response that redirects to an external website or non-hybrid endpoint.

- See also: [`to_external_url`](./functions.md#to-external-url)
- See [external redirects](../../guide/responses.md#external-redirects) for details.

### Usage

```php
return $this->hybridly->createExternalRedirect('https://google.com');
```

## `onDemand`

Creates a property that is only evaluated when explicitly requested through a partial reload.

- See also: [`on_demand`](./functions.md#on_demand)
- See [partial-only properties](../../guide/partial-reloads.md#partial-only-properties) for more details.

### Usage

```php
return $this->hybridly->view('users.show', [
    'user' => UserData::from($user),
    'posts' => $this->hybridly->onDemand(fn () => PostData::collection($user->posts)),
]);
```

## `deferred`

Creates a property that is not included in the initial load, but is automatically loaded in a subsequent partial reload.

- See also: [`deferred`](./functions.md#deferred)

- See [deferred properties](../../guide/partial-reloads.md#deferred-properties) for more details.

### Usage

```php
return $this->hybridly->view('users.show', [
    'user' => UserData::from($user),
    'stats' => $this->hybridly->deferred(
        callback: fn () => UserStatsData::from($user),
        group: 'sidebar',
    ),
]);
```

## `isHybrid`

- See also: [`is_hybrid`](./functions.md#is-hybrid)

Determines whether the current request is hybrid. Optionally, a `Illuminate\Http\Request` instance can be given instead of using the current request.

### Usage

```php
if ($this->hybridly->isHybrid()) {
    // ...
}
```

## `isPartial`

- See also: [`is_partial`](./functions.md#is-partial)

Determines whether the current request is a [partial reload](../../guide/partial-reloads.md). Optionally, a `Illuminate\Http\Request` instance can be given instead of using the current request.

### Usage

```php
if ($this->hybridly->isPartial()) {
  // ...
}
```

## `share`

Shares properties globally across all Hybridly responses.

### Usage

```php
$this->hybridly->share('auth.user', fn () => Auth::user());

$this->hybridly->share([
  'app.name' => config('app.name'),
]);
```

## `flush`

Clears all currently shared global properties.

### Usage

```php
$this->hybridly->flush();
```

## `persist`

Marks one or more property paths as persisted so they are always included, even in partial responses.

### Usage

```php
$this->hybridly->persist([
  'auth',
  'flash',
]);
```

## `resolveVersionUsing`

Sets a callback used to resolve the asset version for responses.

### Usage

```php
$this->hybridly->resolveVersionUsing(fn () => md5_file(public_path('build/manifest.json')));
```

## `setUrlResolver`

Defines how the URL is resolved for the next response.

### Usage

```php
use Illuminate\Http\Request;

$this->hybridly->setUrlResolver(
  fn (Request $request) => $request->fullUrl(),
);
```

## `getUrlResolver`

Returns the currently configured URL resolver callback.

### Usage

```php
$resolver = $this->hybridly->getUrlResolver();
```

## `renderExceptionsInDevelopment`

Ensures Hybridly exception rendering is enabled in both `production` and `local` environments.

### Usage

```php
$this->hybridly->renderExceptionsInDevelopment();
```

## `renderExceptionsUsing`

Defines the response that should be rendered when a handled exception occurs.

### Usage

```php
use Illuminate\Http\Request;
use Throwable;

$this->hybridly->renderExceptionsUsing(
  fn (Throwable $exception, Request $request) => $this->hybridly->view('errors.server-error', [
    'message' => $exception->getMessage(),
  ]),
);
```

## `handleSessionExpirationUsing`

Defines the response that should be returned when a `419` session expiration occurs.

### Usage

```php
$this->hybridly->handleSessionExpirationUsing(
  fn () => redirect()
    ->route('login')
    ->with('error', 'Your session expired. Please login again.'),
);
```
