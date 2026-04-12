# Dialogs

<p class="preface">
Learn how to create and render back-end driven dialogs with their own URL and properties.
</p>

## Overview

Dialogs are components with their own URL and properties, which are rendered as siblings to view components.

When navigating from a view to a dialog, its component will be mounted as a sibling to the current view component—but when navigated to directly via their URL, the base view component and properties will be loaded first, so the dialog can be rendered on top of it.

## Creating a dialog

Dialogs may be anything you want — most commonly, they will be modals or slideovers. The following component is an example of a dialog component which is shown as a modal.

:::code-group

```vue [app/Users/edit.view.vue]
<script setup lang="ts">
defineProps<{
	user: App.Users.UserData
}>()
</script>

<template>
	<hybrid-dialog>
		<edit-user-form :user />
	</hybrid-dialog>
</template>
```

```vue [app/Components/hybrid-dialog.vue]
<script setup lang="ts">
import { useDialog } from 'hybridly/vue'

const { show, closeLocally, unmount } = useDialog()
</script>

<template>
	<u-modal :open="show" @update:open="(value) => !value && closeLocally()" @after:leave="unmount" :close="false">
		<template #content v-if="$slots.content">
			<slot name="content" :close="closeLocally" />
		</template>
	</u-modal>
</template>
```

:::

In this example, the modal component is implemented as a Nuxt UI [`Modal`](https://ui.nuxt.com/docs/components/modal).

Notice the call to [`unmount`](../api/utils/use-dialog.md#unmount), which is needed to remove the dialog from the DOM after it's closed and its animations have finished.

## Rendering a dialog

Dialogs are rendered the same as normal view components, but need a base view which can be defined by calling the [`base`](../api/laravel/hybridly.md#base) method on the view factory.

The `base` method takes the name of a route and its parameters are arguments. When the dialog is accessed directly via its URL, this route will be used to determine which background view should be used.

```php
use App\Users\UserData;
use App\Users\User;

use function Hybridly\view;

final class UserController
{
    public function edit(User $user): HybridResponse
    {
        Gate::authorize('update', $user);

        return view('users.edit', [
            'user' => UserData::from($user),
        ])->configureDialog(baseUrl: route('user.show', $user));
    }
}
```

## Forcing the base page to be displayed

If you want the base page of a dialog to always be displayed, even when the dialog is opened from a different page, you may set the `alwaysRedirectToBase` parameter of the `base` method to `true`:

```php
return view('users.edit', ['user' => UserData::from($user)])
	->configureDialog(
    baseUrl: route('user.show', $user),
    alwaysRedirectToBase: true,
  );
```

This means that every time that dialog is opened, its background page will no longer be the current page, but the specified base page.

## Keeping the current state of the base page

For performance reasons, you may prefer that the properties of the base page are not updated. You may achieve this by setting the `preserveBaseOnClose` parameter of the `base` method to `true`:

```php
return view('users.edit', ['user' => UserData::from($user)])
	->configureDialog(
    baseUrl: route('user.show', $user),
    preserveBaseOnClose: true,
  );
```

This means that every time that dialog is opened, its background page will no longer be updated.

## Closing a dialog

Navigating away from a dialog will automatically close it.

In addition to the `close` function returned by [`useDialog`](../api/utils/use-dialog.md), it's possible to call `router.dialog.close()`. This function takes the same [options](../api/router/options.md) as any navigation.

```vue
<script setup lang="ts">
defineProps<{
	user: App.Users.UserData
}>()

const { close } = useDialog()
</script>

<template>
	<hybrid-dialog title="Edit user">
		<edit-user-form :user @success="close" />
	</hybrid-dialog>
</template>
```
