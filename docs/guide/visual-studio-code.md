# Visual Studio Code

## Overview

The official [Visual Studio Code extension](https://marketplace.visualstudio.com/items?itemName=innocenzi.vscode-hybridly), while still experimental, offers improved navigation support and auto-completion throughout Hybridly applications.

:::info Customization
The extension currently only supports the default and domain-based architecture. Custom paths specified in `initializeHybridly` or the Vite plugin configuration are not taken into account.
:::

### Layout linking and auto-completion

When using the `<template layout>` syntax, the layout names will be linkified to their corresponding layout files.

<img
  src="../assets/vscode-layouts.jpg"
  alt="Linkified layout files"
  class="rounded-lg shadow-lg mt-8"
/>

### Route linking

When using Laravel or Hybridly's `route` functions, the route name will be linkified to its corresponding controller method.

<img
  src="../assets/vscode-route.jpg"
  alt="Linkified route names"
  class="rounded-lg shadow-lg mt-8"
/>

### Component linking and auto-completion

When rendering a page with the `hybridly` function, the page name will be linkified to its corresponding single-file component.

<img
  src="../assets/vscode-component.jpg"
  alt="Linkified page components"
  class="rounded-lg shadow-lg mt-8"
/>
