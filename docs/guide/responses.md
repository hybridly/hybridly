# Responses

## Overview

Hybrid responses respect a protocol to which the front-end adapter must adhere. A response contains, among other things, the name of the page component and its properties.

To send a response, use the `hybridly` function the same way you would use `view`:

```php
use App\Data\ChirpData;
use App\Models\Chirp;

class ChirpController extends Controller  // [!code focus:4]
{
    public function index()
    {
        $this->authorize('viewAny', Chirp::class);

        $chirps = Chirp::query()
            ->forHomePage()
            ->paginate();

        return hybridly('chirps.index', [ // [!code focus:5]
            'chirps' => ChirpData::collection($chirps),
        ]);
    }
}   // [!code focus]
```

In the example above, the corresponding single-file component would simply accept a `chirps` property of the type `ChirpData`:

```vue
<script setup lang="ts">
defineProps<{
  chirps: Paginator<App.Data.ChirpData>
}>()
```

:::info Paginator
Since paginators are so common, Hybridly provides typings for them. You don't need any setup, the `Paginator` type is global. When using paginators without a `meta` property, you may use `UnwrappedPaginator` instead.
:::

## Internal redirects

When making non-get hybrid requests, you may use redirects to a standard `GET` hybrid endpoint. Hybridly will follow the redirect and update the page accordingly.

```php
class UsersController extends Controller
{
    public function index() // [!code focus:8]
    {
        $users = User::paginate();

        return hybridly('users.index', [
            'users' => UserData::collection($users),
        ]);
    }

    public function store(CreateUserData $data, CreateUser $createUser) // [!code focus:6]
    {
        $createUser->execute($data);

        return to_route('users.index');
    }
}
```

In the example above, using `router.post('/users', { data: user })` would redirect to the user index page with the updated `users` property.

## External redirects

It's often necessary to redirect to an external website, or an internal page that doesn't use Hybridly.

If you redirect using a classic server-side redirection, the front-end adapter will not understand the response and will display an error modal. 

Instead, you may use `hybridly()->external($url)` to iniate a client-side redirect using `window.location`:

```php
hybridly()->external('https://google.com');
```

This method can also be used when dealing with potentially non-hybrid request. In such cases, a normal `RedirectResponse` will be returned instead.


## The view-model pattern

A component of the [model-view-viewmodel](https://en.wikipedia.org/wiki/Model%E2%80%93view%E2%80%93viewmodel) pattern, the view-model, is particularly useful when developing hybrid applications.

Aside from its obvious benefits in terms of separation of concerns, the class representing the view-model may be analyzed to be converted to a TypeScript interface.

In other terms, you may create a view-model that extends [Data](https://github.com/spatie/laravel-data) in order to get its typings for free:

```php
// app/ViewModels/ChirpViewModel // [!code focus:2]
class ChirpViewModel extends Data
{
    public function __construct(
        public readonly ChirpData $chirp,   // [!code focus:4]
        #[DataCollectionOf(ChirpData::class)]
        public readonly PaginatedDataCollection $chirps,
        public readonly string $previous,
    ) {
    }
}
```

A controller can use a data object in place of an array of properties:

```php
// app/Http/Controllers/ChirpsController // [!code focus]
use App\Data\ChirpData;
use App\Models\Chirp;
use App\ViewModels\ChirpViewModel; // [!code focus:6]

class ChirpController extends Controller
{
    public function show(Chirp $chirp) 
    {
        $this->authorize('view', $chirp);

        $comments = $chirp->comments()
          ->withLikeAndCommentCounts()
          ->paginate();

        return hybridly('chirps.show', new ChirpViewModel( // [!code focus:9]
            chirp: ChirpData::from($chirp),
            comments: ChirpData::collection($comments),
            previous: $chirp->parent_id
                ? url()->route('chirp.show', $chirp->parent_id)
                : url()->route('index'),
        ));
    }
}

```

In a single-file component, the properties can then be typed thanks to the generated interface:

```vue
<script setup lang="ts">
const $props = defineProps<App.ViewModels.ChirpViewModel>() // [!code focus]
</script>
```

You end up with proper separation of concerns *and* type safety through TypeScript, while writing the shape of your data in only one place.

:::warning Vue support
Vue doesn't support this syntax natively. To make it work, you need to enable the [`betterDefine`](https://vue-macros.sxzz.moe/features/better-define.html) feature from [Vue Macros](https://vue-macros.sxzz.moe).
:::
