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
	<base-modal title="Edit chirp">
		// [!code hl]
		<create-chirp :chirp="chirp" :edit="true" />
	</base-modal> // [!code hl]
</template>
```

```vue [components/base-modal.vue]
<script setup lang="ts">
defineProps<{
	title?: string
}>()

const { show, close, unmount } = useDialog()
</script>

<template>
	<headless-transition-root
		appear
		as="template"
		:show="show"
		@after-leave="unmount"
	>
		<headless-dialog
			as="div"
			class="relative z-30"
			@close="close"
		>
			<headless-transition-child
				as="template"
				enter="ease-out duration-300"
				enter-from="opacity-0"
				enter-to="opacity-100"
				leave="ease-in duration-200"
				leave-from="opacity-100"
				leave-to="opacity-0"
			>
				<div class="fixed inset-0 bg-gray-500/75 backdrop-blur-sm transition-opacity" />
			</headless-transition-child>

			<div class="fixed inset-0 z-30 overflow-y-auto">
				<div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
					<headless-transition-child
						as="template"
						enter="ease-out duration-100"
						enter-from="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
						enter-to="opacity-100 translate-y-0 sm:scale-100"
						leave="ease-in duration-100"
						leave-from="opacity-100 translate-y-0 sm:scale-100"
						leave-to="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
					>
						<headless-dialog-panel class="relative flex flex-col overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-2xl sm:p-6">
							<div class="mb-2 flex items-center justify-between">
								<headless-dialog-title
									v-if="title"
									as="h3"
									class="text-lg font-medium leading-6 text-gray-800"
									v-text="title"
								/>
								<button
									type="button"
									class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
									@click="close"
								>
									<span class="sr-only">Close</span>
									<i-mdi-close class="size-6" aria-hidden="true" />
								</button>
							</div>
							<div class="sm:flex sm:items-start">
								<div class="mt-3 w-full text-center sm:mt-0 sm:text-left">
									<div class="mt-2 w-full">
										<slot />
									</div>
								</div>
							</div>
						</headless-dialog-panel>
					</headless-transition-child>
				</div>
			</div>
		</headless-dialog>
	</headless-transition-root>
</template>
```
:::

For this example, the modal component is implemented as a Headless UI [`Dialog`](https://headlessui.com/v1/vue/dialog) using the [`Transition`](https://headlessui.com/v1/vue/transition) component to show and animate the dialog when it appears and when it closes.

Notice the call to [`unmount`](../api/utils/use-dialog.md#unmount), which is needed to remove the dialog from the DOM after it's closed and its animations have finished.

## Rendering a dialog

Dialogs are rendered the same as normal view components, but need a base view which can be defined by calling the [`base`](../api/laravel/hybridly.md#base) method on the view factory.

The `base` method takes the name of a route and its parameters are arguments. When the dialog is accessed directly via its URL, this route will be used to determine which background view should be used.

```php
use App\Data\ChirpData;
use App\Models\Chirp;

use function Hybridly\view;

class ChirpController extends Controller
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

### Forcing the base page

You may set the `force` parameter of the `base` method to `true` if you want to force the base page to be displayed when rendering the dialog.

This means that every time that dialog is opened, its background page will no longer be the current page, but the specified base page.

### Keeping the base page

You may set the `keep` parameter of the `base` method to `true` if you want to keep the current view and not update its properties when rendering the dialog.

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
