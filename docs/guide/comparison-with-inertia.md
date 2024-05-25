# Comparison with Inertia

## Features

The following is a non-exhaustive comparison table between Inertia and Hybridly's features.

|                                                 |                      Inertia                      |                          Hybridly                          |
| ----------------------------------------------- | :-----------------------------------------------: | :--------------------------------------------------------: |
| Dialogs                                         | <span class="planned">Planned <sup>1</sup></span> |                 [Yes](../guide/dialogs.md)                 |
| Refining                                        |            <span class="no">No</span>             |                [Yes](../guide/refining.md)                 |
| Tables                                          |            <span class="no">No</span>             |                 [Yes](../guide/tables.md)                  |
| Preloading                                      |            <span class="no">No</span>             |     [Yes](../guide/navigation.md#preloading-requests)      |
| Vue DevTools integration                        |            <span class="no">No</span>             |                    [Yes](./devtools.md)                    |
| Visual Studio Code extension                    |                 Yes <sup>2</sup>                  |               [Yes](./visual-studio-code.md)               |
| TypeScript support for DTOs and enums           |            <span class="no">No</span>             |         [Yes <sup>3</sup>](../guide/typescript.md)         |
| TypeScript support for global properties        |            <span class="no">No</span>             |      [Yes](./global-properties.md#typescript-support)      |
| Properties-only responses                       |            <span class="no">No</span>             |      [Yes](../guide/responses.md#updating-properties)      |
| Partial reloads                                 |                        Yes                        |             [Yes](../guide/partial-reloads.md)             |
| Lazy properties                                 |                        Yes                        | [Yes](../guide/partial-reloads.md#partial-only-properties) |
| Deferred properties                             |            <span class="no">No</span>             |   [Yes](../guide/partial-reloads.md#deferred-properties)   |
| Persistent properties                           |                   Yes (v1.2.0)                    |             [Yes](./persistent-properties.md)              |
| Global properties                               |                        Yes                        |            [Yes](../guide/global-properties.md)            |
| `only` support for partial properties           |                        Yes                        |            [Yes](../api/router/options.md#only)            |
| `except` support for partial properties         |                   Yes (v1.1.0)                    |           [Yes](../api/router/options.md#except)           |
| Dot notation support for partial properties     |                   Yes (v1.2.0)                    |                            Yes                             |
| Infinite scrolling support                      |            <span class="no">No</span>             |        [Yes](../api/router/options.md#preserveurl)         |
| Custom architecture support                     |            <span class="no">No</span>             |              [Yes](../guide/architecture.md)               |
| Persistent layout                               |                        Yes                        |       [Yes](views-and-layouts.md#persistent-layouts)       |
| Persistent layout properties                    |            <span class="no">No</span>             |  [Yes](views-and-layouts.md#persistent-layout-properties)  |
| Vite integration                                |            <span class="no">No</span>             |              [Yes](../configuration/vite.md)               |
| Auto-imports                                    |            <span class="no">No</span>             |        [Yes](../configuration/vite.md#auto-imports)        |
| Icons support                                   |            <span class="no">No</span>             |           [Yes](../configuration/vite.md#icons)            |
| `layout` support in templates                   |            <span class="no">No</span>             |                [Yes](views-and-layouts.md)                 |
| Built-in `form` util                            |                        Yes                        |              [Yes](../api/utils/use-form.md)               |
| Built-in `route` util with TypeScript support   |            <span class="no">No</span>             |                [Yes](../api/utils/route.md)                |
| Built-in `can` util with TypeScript support     |            <span class="no">No</span>             |                 [Yes](../api/utils/can.md)                 |
| Built-in `Paginator` types                      |            <span class="no">No</span>             |               [Yes](./responses.md#overview)               |
| Built-in testing utils                          |                        Yes                        |              [Yes](../api/laravel/testing.md)              |
| Local navigation support (no server round-trip) |            <span class="no">No</span>             |            [Yes](../api/router/utils.md#local)             |
| Server-side rendering                           |                        Yes                        | <span class="planned" title="at some point">Planned</span> |
| Built-in meta management                        |                        Yes                        |          <span class="no">No <sup>4</sup></span>           |
| Internationalization support                    |            <span class="no">No</span>             |                  [Yes](../guide/i18n.md)                   |
| Property case conversion support                |            <span class="no">No</span>             |             [Yes](../guide/case-conversion.md)             |
| Exposed back-forward detection                  |            <span class="no">No</span>             |          [Yes](../api/utils/use-back-forward.md)           |
| Programmatic navigation                         |                        Yes                        |       [Yes](../guide/navigation.md#programmatically)       |
| Front-end-initiated external navigation support |            <span class="no">No</span>             |           [Yes](../api/router/utils.md#external)           |
| Back-end-initiated external navigation support  |                        Yes                        |     [Yes](../api/laravel/functions.md#to_external_url)     |
| Disable progress bar per navigation             |            <span class="no">No</span>             |          [Yes](../api/router/options.md#progress)          |
| Custom Axios instance support                   |            <span class="no">No</span>             |      [Yes](../api/utils/initialize-hybridly.md#axios)      |
| Request events support                          |                        Yes                        |           [Yes](../api/router/options.md#hooks)            |
| Global events support                           |                        Yes                        |                  [Yes](../guide/hooks.md)                  |
| Framework-agnostic core                         |                        Yes                        |                          Kind of                           |
| Official React, Svelte, Rails, Django support   |                        Yes                        |                 <span class="no">No</span>                 |
| Link component                                  |                        Yes                        |          [Yes](../api/components/router-link.md)           |
| Backed by the 🐐 at Laravel                      |                        Yes                        |                 <span class="no">No</span>                 |

<div class="opacity-80">
  1. Support through third-party package using <a href="https://github.com/lepikhinb/momentum-modal">Momentum</a> <br />
  2. Unofficial extension by <a href="https://twitter.com/nicolashedger">Nicolas Hedger</a> <br />
  3. Support through Laravel Data <br />
  4. <a href="https://unhead.unjs.io/"><code>@unhead/vue</code></a> is recommended instead <br />
</div>

<style>
table a {
  @apply underline decoration-dashed decoration-offset-4;
}

.no {
  @apply font-medium dark:text-red-400/50 text-red-700/50;
}

.planned {
  opacity: .5;
}

tbody > tr > td {
  width: 100%;
  white-space: nowrap;
}
</style>
****
