# Comparison with Inertia

## Features

The following is a non-exhaustive comparison table between Inertia and Hybridly's features.

|                                                 |                      Inertia                      |                          Hybridly                          |
| ----------------------------------------------- | :-----------------------------------------------: | :--------------------------------------------------------: |
| Link component                                  |                        Yes                        |          [Yes](../api/components/router-link.md)           |
| Programmatic navigation                         |                        Yes                        |       [Yes](../guide/navigation.md#programmatically)       |
| Back-end-initiated external navigation support  |                        Yes                        |     [Yes](../api/laravel/functions.md#to_external_url)     |
| Front-end-initiated external navigation support |            <span class="no">No</span>             |           [Yes](../api/router/utils.md#external)           |
| Local navigation support (no server round-trip) |            <span class="no">No</span>             |            [Yes](../api/router/utils.md#local)             |
| Global properties                               |                        Yes                        |            [Yes](../guide/global-properties.md)            |
| TypeScript support for DTOs and enums           |            <span class="no">No</span>             |         [Yes <sup>1</sup>](../guide/typescript.md)         |
| TypeScript support for global properties        |            <span class="no">No</span>             |      [Yes](./global-properties.md#typescript-support)      |
| Persistent properties                           |            <span class="no">No</span>             |             [Yes](./persistent-properties.md)              |
| Preloading                                      |            <span class="no">No</span>             |     [Yes](../guide/navigation.md#preloading-requests)      |
| Partial reloads                                 |                        Yes                        |             [Yes](../guide/partial-reloads.md)             |
| Inclusion support for partials                  |                        Yes                        |            [Yes](../api/router/options.md#only)            |
| Exclusion support for partials                  |            <span class="no">No</span>             |           [Yes](../api/router/options.md#except)           |
| Dot notation support for partials               |            <span class="no">No</span>             |                            Yes                             |
| Persistent layout                               |                        Yes                        |  [Yes](../guide/pages-and-layouts.md#persistent-layouts)   |
| Persistent layout properties                    |            <span class="no">No</span>             |   [Yes](../api/composables/define-layout-properties.md)    |
| Vite integration                                |            <span class="no">No</span>             |              [Yes](../configuration/vite.md)               |
| Auto-imports                                    |            <span class="no">No</span>             |        [Yes](../configuration/vite.md#auto-imports)        |
| Icons support                                   |            <span class="no">No</span>             |           [Yes](../configuration/vite.md#icons)            |
| `layout` support in templates                   |            <span class="no">No</span>             |            [Yes](../guide/pages-and-layouts.md)            |
| Built-in `Paginator` types                      |            <span class="no">No</span>             |               [Yes](./responses.md#overview)               |
| Built-in `form` util                            |                        Yes                        |           [Yes](../api/composables/use-form.md)            |
| Built-in `route` util with TypeScript support   |            <span class="no">No</span>             |                [Yes](../api/utils/route.md)                |
| Built-in `can` util with TypeScript support     |            <span class="no">No</span>             |                 [Yes](../api/utils/can.md)                 |
| Built-in testing utils                          |                        Yes                        |              [Yes](../api/laravel/testing.md)              |
| Auto-conversion to `FormData`                   |                        Yes                        |                            Yes                             |
| Infinite scroll support                         |            <span class="no">No</span>             |        [Yes](../api/router/options.md#preserveurl)         |
| Server-side rendering                           |                        Yes                        | <span class="planned" title="at some point">Planned</span> |
| Built-in meta management                        |                        Yes                        |          <span class="no">No <sup>2</sup></span>           |
| Dialogs                                         | <span class="planned">Planned <sup>3</sup></span> |                 [Yes](../guide/dialogs.md)                 |
| Refining                                        |            <span class="no">No</span>             |                [Yes](../guide/refining.md)                 |
| Request events support                          |                        Yes                        |           [Yes](../api/router/options.md#hooks)            |
| Global events support                           |                        Yes                        |                  [Yes](../guide/hooks.md)                  |
| Internationalization support                    |            <span class="no">No</span>             |                  [Yes](../guide/i18n.md)                   |
| Property case conversion support                |            <span class="no">No</span>             |             [Yes](../guide/case-conversion.md)             |
| Exposed back-forward detection                  |            <span class="no">No</span>             |       [Yes](../api/composables/use-back-forward.md)        |
| Framework-agnostic core                         |                        Yes                        |                            Yes                             |
| Custom architecture support                     |            <span class="no">No</span>             |              [Yes](../guide/architecture.md)               |
| Custom Axios instance support                   |            <span class="no">No</span>             |      [Yes](../api/utils/initialize-hybridly.md#axios)      |
| Official React, Svelte, Rails, Django support   |                        Yes                        |                 <span class="no">No</span>                 |
| Vue DevTools integration                        |            <span class="no">No</span>             |                    [Yes](./devtools.md)                    |
| Visual Studio Code extension                    |                 Yes <sup>4</sup>                  |               [Yes](./visual-studio-code.md)               |
| Backed by the 🐐 at Laravel                      |                        Yes                        |                 <span class="no">No</span>                 |

<div class="opacity-80">
  1. Support through Laravel Data <br />
  2. <a href="https://github.com/vueuse/head"><code>@vueuse/head</code></a> is recommended instead <br />
  3. Support through third-party package using <a href="https://github.com/lepikhinb/momentum-modal">Momentum</a> <br />
  4. Unofficial extension by <a href="https://twitter.com/nicolashedger">Nicolas Hedger</a> <br />
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
