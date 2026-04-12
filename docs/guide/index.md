---
outline: "deep"
---

# Introduction

<div class="preface">
Hybridly can be installed on a Laravel application to make it easy to build applications using Vue instead of Blade, while keeping the benefits of classic monolithic applications.
</div>

## Overview

Using a protocol similar to the one [Jonathan Reinink](https://reinink.ca) invented for [Inertia](https://inertiajs.com), Hybridly makes it possible to build applications using Vue instead of Blade, while keeping the benefits of classic monolithic applications.

Hybridly is essentially very similar to Inertia, but it has a different philosophy. Since it focuses on Laravel, Vite and Vue instead of being completely framework-agnostic, it **has more built-in features** and **quality of life improvements**, which results in a **better developer experience** overall.

In other words, Hybridly is more like a framework built on top of Laravel and Vue, focusing specifically on being the perfect glue between the two.

## What it looks like

Working with Hybridly is pretty similar to working with basic Laravel. The main difference is how you render views, since Hybridly uses Vue.

Due to the nature of client-rendered applications, you also lose the access of some features like [`route`](https://laravel.com/docs/11.x/urls#urls-for-named-routes) or [`@can`](https://laravel.com/docs/11.x/authorization#via-blade-templates) in templates. Fortunately, we have alternatives for them!

Below are some basic examples of what Hybridly code looks like:

### Controllers

Controllers look the same as what you are used to with Laravel. The main difference is that you return [hybrid responses](./responses.md) using the [`Hybridly\view`](../api/laravel/functions.md#view) function instead of Laravel's built-in `view`.

:::code-group

```php [UserProfileController.php]
use App\Users\UserData;
use App\Users\User;
use App\Users\UpdateUserRequest;
use Hybridly\Contrats\HybridResponse;

use function Hybridly\view;

final readonly class UserProfileController
{
    public function show(User $user): HybridResponse
    {
        return view('users.show', [
            'user' => UserData::from($user)
        ]);
    }

    public function update(User $user, UpdateUserRequest $request): RedirectResponse
    {
        $user->update($request->validated());

        return back()->with('success', 'Changes saved.');
    }
}
```

```php [routes.php]
Route::get('/users/{user}', [UserProfileController::class, 'show'])
  ->name('users.show');

Route::put('/users/{user}/update', [UserProfileController::class, 'update'])
  ->name('users.update');
```

:::

### Templates

Hybridly uses [Vue](https://vuejs.org) to render pages using single-file components. You may use [layouts](./views-and-layouts.md#layouts) using a simple directive.

```vue [show.vue]
<script setup lang="ts">
import { Form } from 'hybridly/vue'

defineProps<{
	user: App.Users.UserData
}>()
</script>

<template layout="user-profile">
	<user-card :user />
	<!-- this is a Form component provided by Hybridly -->
	<Form @submit="form.submit()" :action="route('users.update', { user })">
		<input name="name" label="Name" />
		<input name="email" label="Email" type="email" />
		<button type="submit">Update profile</button>
	</Form>
</template>
```

There are a few things going on there:

- The `template` block has a `layout` directive that specifies which persistent layout is being used for this page. Learn more about [layouts](./views-and-layouts.md#persistent-layouts).

- The `user` property is typed using an auto-generated interface from a data object. You can learn more about TypeScript integration [here](./typescript.md).

- We use the [`<Form>`](../api//components/form.md) component or the [`useForm`](../api/utils/use-form.md) util to work with forms. Learn more about them on the [forms documentation](./forms.md).

- Hybridly provides a [`route`](../api/utils/route.md) util to generate URLs, similar to Laravel's [`route`](https://laravel.com/docs/11.x/urls#urls-for-named-routes) helper.

### Beyonds the basics

Rendering a single page with a form is cool, but real-world applications are more complex.

After learning about essential features using the sidebar to your left, you may want to learn about:

- [How to render dialogs](./dialogs.md)
- [How to implement filters and sorts](./refining.md)
- [How to implement data tables](./tables.md)

&nbsp;

## About Inertia and Hybridly

I was barely into the Laravel ecosystem when Jonathan Reinink was already looking for a way to [build Vue-powered Laravel applications](https://reinink.ca/articles/server-side-apps-with-client-side-rendering) the right way.

He came up with Inertia, which is now backed by Laravel. It powers [Forge](https://forge.laravel.com) and [Laravel Cloud](https://cloud.laravel.com). It is a well-established tool. If you already build applications using Inertia and you don't feel like you should change your stack, there is no need to reach for a different tool.

**Hybridly came to life because of Inertia's history**. When it was first released, its philosophy was to stay very minimalist—it had very few features beyond basic routing and rendering. Moreover, its pace of development has been a source of frustration for its users, with pull requests and issues not being handled for months.

Nowadays, Inertia has changed a lot. It is very featureful, supports TypeScript and is updated regurlarly.

However, Hybridly is here to stay. It is what I use for development, and I will keep maintaining it for the foreseeable future. It is a more opinionated tool, with different built-in features and quality of life improvements, the most notable ones being support for [dialogs](./dialogs.md) and [data tables](./tables.md).
