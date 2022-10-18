# Hooks

## Overview

Hybridly's requests have their own lifecycle. It's sometimes necessary to hook into some of its events to perform custom logic.

For instance, the progress bar is implemented using these hooks.

There are a two main ways to catch Hybridly's events: globally, through [plugins](./plugins.md), or locally, through [visit options](../api/utils/router.md).

## Plugins

Plugins can be registered through [`initializeHybridly`](../api/vue.md)'s `plugins` option. They have access to all lifecycle events, plus an additionnal plugin-specific hook, `initialize`.

They are useful when you need to execute custom logic for each request. You can learn more about plugins in [their documentation](./plugins.md).

## Registering hooks manually

Though it's highly recommended to use plugins, it's also possible to register hooks with the `registerHook` and `registerHookOnce` functions.

This may be useful for requirements for which plugins could be overkill.

```ts
<script setup lang="ts">
registerHook('navigate', ({ isBackForward }) => { // [!vp focus:5]
  if (isBackForward) {
    router.reload()
  }
})
</script>
```

## Visit options

When [navigating](./navigation.md) or using the [form util](./forms.md), it's possible to pass a `hook` object that accepts a callback for each lifecycle event.

These callbacks will be executed just once for the current request. You can learn more about visit options in [their documentation](../api/utils/router.md).

## Available hooks

Twelve events can be hooked into when hybrid requests are made. It's worth noting that each hook is awaitable and may return a promise that will be waited for.

You can learn more about individual hooks in their [API documentation](../api/utils/router.md), or you can read their interface below:

```ts
export interface Hooks {
	/**
	 * Called before anything when a visit is going to happen.
	 */
	before: (options: VisitOptions) => MaybePromise<any | boolean>

	/**
	 * Called before the request of a visit is going to happen.
	 */
	start: (context: InternalRouterContext) => MaybePromise<any>

	/**
	 * Called when progress on the request is being made.
	 */
	progress: (progress: Progress) => MaybePromise<any>

	/**
	 * Called when data is received after a request for a visit.
	 */
	data: (response: AxiosResponse) => MaybePromise<any>

	/**
	 * Called when a request is successful and there is no error.
	 */
	success: (payload: VisitPayload) => MaybePromise<any>

	/**
	 * Called when a request is successful but there were errors.
	 */
	error: (errors: Errors) => MaybePromise<any>

	/**
	 * Called when a request has been aborted.
	 */
	abort: (context: InternalRouterContext) => MaybePromise<any>

	/**
	 * Called when a response to a request is not a valid hybrid response.
	 */
	invalid: (response: AxiosResponse) => MaybePromise<void>

	/**
	 * Called when an unknowne exception was triggered.
	 */
	exception: (error: Error) => MaybePromise<void>

	/**
	 * Called whenever the request failed, for any reason, in addition to other hooks.
	 */
	fail: (context: InternalRouterContext) => MaybePromise<void>

	/**
	 * Called after a request has been made, even if it didn't succeed.
	 */
	after: (context: InternalRouterContext) => MaybePromise<void>

	/**
	 * Called when a visit has been made and a page component has been navigated to.
	 */
	navigate: (options: NavigationOptions) => MaybePromise<void>
}
```
