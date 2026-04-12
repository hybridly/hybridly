# Responses

<p class="preface">
Learn how the Hybridly protocol works and how to return different kinds of responses to the front-end.
</p>

## Overview

Hybrid responses respect a protocol to which the front-end adapter must adhere. A response contains, among other things, the name of the view component and its properties.

To send a response, you would typically use the [`Hybridly\view`](../api/laravel/functions.md#view) function, which renders a view and its properties just like Laravel's own `view` function:

```php
use App\Users\User;
use App\Users\UserData;

final readonly class ShowUserController
{
    public function __invoke(User $user): HybridResponse
    {
        Gate::authorize('view', $user);

        return view('users.show', [
            'user' => UserData::fromModel($user),
        ]);
    }
}
```

In the example above, the corresponding single-file component would simply accept a `user` property of the type `UserData`:

```vue
<script setup lang="ts">
defineProps<{ // [!code focus:3]
	user: App.Users.UserData
}>()
</script>
```

## Updating properties

It is a common pattern to have a `POST` or `PUT` hybrid request that ends up redirecting back to the previous page, which essentially refreshes the properties of the page to avoid having stale data.

```php
public function store(UpdateUserRequest $request): HybridResponse
{
    User::query()->update($request->validate());

    return back();
}
```

Such a redirection, though, implies an additional server round-trip and the re-execution of the server-side controller responsible for the view, which might slow down the response, depending on the complexity of the page.

If you need a performance boost, you may return only properties from the `POST` or `PUT` controller:

```php
use function Hybridly\properties;

public function store(UpdateUserRequest $request): HybridResponse
{
    $user = User::query()->update($request->validate());

    return properties([
        'user' => $user,
    ]);
}
```

In that situation, the returned properties will be merged with the current ones, similarly to what happens during a [partial reload](./partial-reloads.md).

## Internal redirects

When making non-get hybrid requests, you may use redirects to a standard `GET` hybrid endpoint. Hybridly will follow the redirect and update the page accordingly.

```php
final readonly class UsersController
{
    public function index(): HybridResponse // [!code focus:8]
    {
        $users = User::query()->paginate();

        return view('users.index', [
            'users' => UserData::collection($users),
        ]);
    }

    public function store(CreateUserData $data, CreateUser $createUser): RedirectResponse // [!code focus:6]
    {
        $createUser->execute($data);

        return to_route('users.index'); // Redirects to `index` above // [!code hl]
    }
}
```

In the example above, using `router.post('/users', { data: user })` would redirect to the user index page with the updated `users` property.

## External redirects

It's often necessary to redirect to an external website, or sometimes even an internal page that doesn't use Hybridly, such as a Filament panel.

If you use a classic server-side redirection, the front-end adapter will not understand the response and will display an error modal.

Instead, you may use [`Hybridly\to_external_url($url)`](../api/laravel/functions.md#to_external_url) to iniate a client-side redirect using `window.location`:

```php
use function Hybridly\to_external_url;

to_external_url('https://google.com');
```

You may also open the URL in a new tab by specifying a target:

```php
use Hybridly\Support\Target;
use function Hybridly\to_external_url;

to_external_url('https://google.com', target: Target::NEW_TAB);
```

### Potentially non-hybrid requests

If you are not sure whether the current request expects a hybrid response, you may still use [`to_external_url`](../api/laravel/functions.md#to_external_url).

Under the hood, it will detect if the request is hybrid and use a normal `RedirectResponse` instead if necessary.

## File downloads

Download responses using a `Content-Disposition` header are supported.

You may use any of the usual utilities for creating downloads, such as [`download`](https://laravel.com/docs/master/responses#file-downloads), [`streamDownload`](https://laravel.com/docs/master/responses#streamed-downloads), or [`Storage::download`](https://laravel.com/docs/10.x/filesystem#downloading-files).

```php
return response()->download($invoice->file_path, 'invoice.pdf');
```

However, [in-browser file responses](https://laravel.com/docs/master/responses#file-responses) are not supported, as there is no way for Hybridly to differentiate it from a normal response.
