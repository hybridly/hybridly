# Global functions

These functions are available globally when `hybridly/laravel` is installed.

## `hybridly`

When called without a parameter, this functions returns the [`Hybridly\Hybridly`](./instance.md) singleton instance.

Otherwise, it is an alias of [`hybridly()->view()`](./instance.md#view).

## `is_hybrid`

Determines whether the current request is hybrid. If a `Illuminate\Http\Request` instance is given, uses this instance instead of the current request.

### Usage

```php
if (is_hybrid()) {
  // ...
}
```
