# Authorization

## Overview

Authorization is what ensures an entity has the ability to perform a given task. Laravel provides gates and policies — they are simple but powerful ways to answer this problem.

Authorization needs to be performed on the server. This is usually done through `User#can` or `Gate::authorize`. Unfortunately, this is not accessible when working in single-file components.

Hybridly solves this issue by providing a decorator around [data objects](./typescript.md#data-objects). This decorator makes policies' actions typeable, so they can be used in single-file component by the [`can`](../api/utils/can.md) function.

## Using data resources

First, a data object extending `Hybridly\Support\Data\DataResource` needs to be created. This class exposes an `$authorizations` array which should contain the names of the actions that need to be exposed.

```php
<?php

namespace App\Data;

use Carbon\Carbon;
use Hybridly\Support\Data\DataResource;  // [!code focus]

final class ChirpData extends DataResource  // [!code focus]
{
    public static array $authorizations = [  // [!code focus:6]
      'comment',
      'like',
      'unlike',
      'delete'
    ];

    public function __construct(
        public readonly string $id,
        public readonly ?string $body,
        public readonly int $likes_count,
        public readonly int $comments_count,
        public readonly Carbon $created_at,
    ) {}
}
```

When [transforming](https://spatie.be/docs/laravel-data/v2/as-a-resource/from-data-to-resource) a data resource, a [lazy `authorization` property](https://spatie.be/docs/laravel-data/v2/as-a-resource/lazy-properties) will be appended to the resulting array.

This property will contain a key for each defined policy action, and will be evaluated through `Gate::allows`:

```json
{
	"id": "1514",
	"body": "Ad nihil provident rem voluptatem quis modi harum ad. Tenetur sunt nisi libero qui debitis.",
	"likes_count": 1,
	"comments_count": 3,
	"created_at": "2022-10-12T17:44:05+00:00",
	"authorization": { // [!code focus:6]
    "comment": false,
    "like": false,
    "unlike": true,
    "delete": false
  }
}
```

The policy for the previous example could look like that:

```php
use App\Models\Chirp;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ChirpPolicy
{
    use HandlesAuthorization;

    public function comment(User $user): bool  // [!code focus:4]
    {
        return true;
    }

    public function delete(User $user, Chirp $chirp): bool  // [!code focus:4]
    {
        return $chirp->author->is($user);
    }

    public function like(User $user, Chirp $chirp): bool  // [!code focus:4]
    {
        return !$user->hasLiked($chirp);
    }

    public function unlike(User $user, Chirp $chirp): bool  // [!code focus:4]
    {
        return $user->hasLiked($chirp);
    }
}
```

## Authorizing on the front-end

When sharing a property from a data resource to the front-end, authorizations could directly be checked against the data object, but the `can` util provides a better syntax.

```ts
import { can } from 'hybridly' // [!code focus]

const $props = defineProps<{
  chirp: App.Data.ChirpData
}>()

// With the `can` util (recommended) // [!code focus:2]
const canComment = can($props.chirp, 'comment')

// As-is  // [!code focus:2]
const canComment = $props.chirp.authorization.comment
```


## Avoid processing authorizations

The method Hybridly uses to provide automatic authorizations has a drawback: the gate is called for each action, each time the data object is serialized.

Fortunately, this is built on top of `laravel-data`'s [lazy properties](https://spatie.be/docs/laravel-data/v2/as-a-resource/lazy-properties), which mean you can simply call `->exclude('authorization')` for them to not be processed.

```php
public function show(Chirp $chirp)
{
    $this->authorize('view', $chirp);

    return hybridly('chirps.show', [
        'chirp' => ChirpData::from($chirp)->exclude('authorization'), // [!code focus]
    ]);
}
```
