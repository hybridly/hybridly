# `useProperties`

This composable returns a reactive object containing all properties, global and local. Global properties will be typed if [TypeScript support for them](../../guide/global-properties.md#typescript-support) is set up properly.

## Usage

```ts
function useProperties<T>(): GlobalHybridlyProperties & T
```

`useProperties` does not accept any argument, and returns a `reactive` value of all properties, global and local.

Since local properties can not be inferred, the function accepts a generic argument for typing them.
