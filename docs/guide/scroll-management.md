# Scroll management

## Overview

When navigating between pages, Hybridly simulates default browser behavior by automatically resettting the scroll position of the body back to the top.

Additionally, the scroll position of each page is automatically restored when navigating back and forward in history.

## Preserving scroll position

In some situations, it's desirable to prevent resetting the scroll position. This behavior can be disabled by setting the `preserveScroll` option to `true`.

```ts
router.get(url, { preserveScroll: true })
```

For instance, you may want to toggle a user setting after a click on a button by making a hybrid `POST` request on an endpoint and redirecting back. In that situation, it's not desirable to reset the scroll position.

## Scroll regions

You may optionally define elements which scroll positions should be kept track of. This is done by applying the `scroll-region` attribute to an element.

```html
<div class="overflow-y-auto" scroll-region>
  <!-- ... -->
</div>
```

The scroll position of these elements will be restored when navigating back and forward in the history.
