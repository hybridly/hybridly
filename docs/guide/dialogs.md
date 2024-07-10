# Dialogs

## Overview

Dialogs are components with their own URL and properties, which are rendered as siblings to view components.

When navigating from a view to a dialog, its component will be mounted as a sibling to the current view component—but when navigated to directly via their URL, the base view component and properties will be loaded first, so the dialog can be rendered on top of it.

:::info Experimental
This feature has not been dogfed yet and is considered experimental. Its API may change at any time. Feel free to give feedback on our Discord server.
:::

## Creating a dialog

Dialogs may be anything you want — most commonly, they will be modals or slideovers. The following component is an example of a dialog component which is shown as a modal.

:::code-group
```vue [views/chirps/edit.vue]
<script setup lang="ts">
defineProps<{
	chirp: App.Data.ChirpData
}>()
</script>

<template>
	<base-dialog>
		<edit-chirp :chirp="chirp" />
	</base-dialog>
</template>
```

```vue [components/base-modal.vue]
<script setup lang="ts">
import { DialogClose, DialogContent, DialogOverlay, DialogPortal, DialogRoot } from 'radix-vue'

const { show, close, unmount } = useDialog()
</script>

<template>
	<dialog-root :open="show">
		<dialog-portal>
			<dialog-overlay class="data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 fixed inset-0 z-50 bg-black/50 backdrop-blur-sm" />
			<dialog-content
				class="bg-background data-[state=open]:animate-in data-[state=closed]:animate-out data-[state=closed]:fade-out-0 data-[state=open]:fade-in-0 data-[state=closed]:zoom-out-95 data-[state=open]:zoom-in-95 data-[state=closed]:slide-out-to-left-1/2 data-[state=closed]:slide-out-to-top-[48%] data-[state=open]:slide-in-from-left-1/2 data-[state=open]:slide-in-from-top-[48%] fixed left-1/2 top-1/2 z-50 w-full max-w-sm -translate-x-1/2 -translate-y-1/2 gap-4 border p-5 shadow-lg duration-200 sm:rounded-lg"
				@interact-outside="close"
				@escape-key-down="close"
				@pointer-down-outside="close"
				@after-leave="unmount"
			>
				<slot :close="close" />
				<dialog-close as-child>
					<slot name="close" :close="close" />
				</dialog-close>
			</dialog-content>
		</dialog-portal>
	</dialog-root>
</template>
```
:::

In this example, the modal component is implemented as a Radix [`Dialog`](https://www.radix-vue.com/components/dialog.html) using the [`tailwindcss-animate`](https://github.com/jamiebuilds/tailwindcss-animate) plugin to animate it.

Notice the call to [`unmount`](../api/utils/use-dialog.md#unmount), which is needed to remove the dialog from the DOM after it's closed and its animations have finished.

## Rendering a dialog

Dialogs are rendered the same as normal view components, but need a base view which can be defined by calling the [`base`](../api/laravel/hybridly.md#base) method on the view factory.

The `base` method takes the name of a route and its parameters are arguments. When the dialog is accessed directly via its URL, this route will be used to determine which background view should be used.

```php
use App\Data\ChirpData;
use App\Models\Chirp;

use function Hybridly\view;

final class ChirpController
{
    public function edit(Chirp $chirp)
    {
        $this->authorize('edit', $chirp);

        return view('chirps.edit', [ // [!code focus:3]
            'chirp' => ChirpData::from($chirp),
        ])->base('chirp.show', $chirp); // [!code hl]
    }
}
```

## Forcing the base page to be displayed

If you want the base page of a dialog to always be displayed, even when the dialog is opened from a different page, you may set the `force` parameter of the `base` method to `true`:

```php
return view('chirps.edit', ['chirp' => ChirpData::from($chirp)])
	->base('chirp.show', $chirp, force: true);
```

This means that every time that dialog is opened, its background page will no longer be the current page, but the specified base page.

## Keeping the current state of the base page

For performance reasons, you may prefer that the properties of the base page are not updated. You may achieve this by setting the `keep` parameter of the `base` method to `true`:

```php
return view('chirps.edit', ['chirp' => ChirpData::from($chirp)])
	->base('chirp.show', $chirp, keep: true);
```

This means that every time that dialog is opened, its background page will no longer be updated.

## Closing a dialog

Navigating away from a dialog will automatically close it.

In addition to the `close` function returned by [`useDialog`](../api/utils/use-dialog.md), it's possible to call `router.dialog.close()`. This function takes the same [options](../api/router/options.md) as any navigation.

```vue
<script setup lang="ts">
defineProps<{
	chirp: App.Data.ChirpData
}>()

const { close } = useDialog() // [!code hl]
</script>

<template>
	<base-modal title="Edit chirp">
		<create-chirp
			:chirp="chirp"
			:edit="true"
			@success="close/* [!code hl] */"
		/>
	</base-modal>
</template>
```
