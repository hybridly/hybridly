# Authorization

<p class="preface">
Learn how to handle authorization in your single-file components, and how to avoid it when not needed.
</p>

## Overview

Authorization is what ensures an entity has the ability to perform a given task. Laravel provides gates and policies — they are simple but powerful ways to answer this problem.

Authorization needs to be performed on the server. This is usually done through `User#can` or `Gate::authorize`. Unfortunately, this is not accessible when working in single-file components.

## Authorizing on the front-end

The recommend approach is to share authorization information as part of the shared data. This way, you can easily check for permissions on the front-end without having to make additional requests.

:::code-group

```php [App/Users/ShowUsersController.php]
final class ShowUsersController
{
    public function __invoke(): HybridResponse
    {
        $users = User::query()
            ->where('active', true)
            ->get();

        return view('users.index', [
            'can' => [
                'create_user' => Auth::user()->can('create', User::class),
            ],
            'users' => UserData::collect($users, into: 'array'),
        ]);
    }
}
```

```php [App/Users/UserData.php]
final class UserData extends Data
{
    public function __construct(
        public readonly string $name,
        public readonly string $email,
        public readonly bool $can_update,
    ) {}

    public static function fromModel(User $user): self
    {
        return new self(
            name: $user->name,
            email: $user->email,
            can_update: Auth::user()->can('update', $user),
        );
    }
}
```

:::

## Sharing authorizations globally

If you need authorization information to be available globally, you can share it through a dedicated middleware.

```php
use Hybridly\Hybridly;

final readonly class ShareAuthorizations
{
    public function __construct(
        private Hybridly $hybridly,
    ) {}

    public function __invoke(Request $request, Closure $next): Response
    {
        $this->hybridly->persist('authorizations');
        $this->hybridly->share('authorizations', new AuthorizationData(
            can_create_user: $request->user()->can('create', User::class),
            can_create_post: $request->user()->can('create', Post::class),
        ));

        return $next($request);
    }
}
```

Learn more on the [global properties](../guide/global-properties.md) documentation.
