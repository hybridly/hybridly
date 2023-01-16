# Comparison with Inertia

## Features

The following is a non-exhaustive comparison table between Inertia and Hybridly's features.

|                                                 |                      Inertia                      |                       Hybridly                        |
| ----------------------------------------------- | :-----------------------------------------------: | :---------------------------------------------------: |
| Link component                                  |                        Yes                        |                          Yes                          |
| Programmatic navigation                         |                        Yes                        |                          Yes                          |
| Back-end-initiated external navigation support  |                        Yes                        |                          Yes                          |
| Front-end-initiated external navigation support |            <span class="no">No</span>             |        [Yes](../api/router/utils.md#external)         |
| Local navigation support (no server round-trip) |            <span class="no">No</span>             |          [Yes](../api/router/utils.md#local)          |
| Global properties                               |                        Yes                        |                          Yes                          |
| TypeScript support for global properties        |            <span class="no">No</span>             |   [Yes](./global-properties.md#typescript-support)    |
| Persistent properties                           |            <span class="no">No</span>             |           [Yes](./persistent-properties.md)           |
| Partial reloads                                 |                        Yes                        |                          Yes                          |
| Inclusion support for partials                  |                        Yes                        |                          Yes                          |
| Exclusion support for partials                  |            <span class="no">No</span>             |        [Yes](../api/router/options.md#except)         |
| Dot notation support for partials               |            <span class="no">No</span>             |                          Yes                          |
| Persistent layout                               |                        Yes                        |                          Yes                          |
| Persistent layout properties                    |            <span class="no">No</span>             | [Yes](../api/composables/define-layout-properties.md) |
| Vue DevTools integration                        |            <span class="no">No</span>             |                 [Yes](./devtools.md)                  |
| Vite integration                                |            <span class="no">No</span>             |                          Yes                          |
| `layout` support in templates                   |            <span class="no">No</span>             |             [Yes](../api/vite/layout.md)              |
| Built-in `Paginator` types                      |            <span class="no">No</span>             |            [Yes](./responses.md#overview)             |
| Built-in `form` util                            |                        Yes                        |                          Yes                          |
| Built-in `route` util with TypeScript support   |            <span class="no">No</span>             |             [Yes](../api/utils/route.md)              |
| Built-in `can` util with TypeScript support     |            <span class="no">No</span>             |              [Yes](../api/utils/can.md)               |
| Built-in testing utils                          |                        Yes                        |                          Yes                          |
| Auto-conversion to `FormData`                   |                        Yes                        |                          Yes                          |
| Infinite scroll support                         |            <span class="no">No</span>             |      [Yes](../api/router/options.md#preserveurl)      |
| Server-side rendering                           |                        Yes                        |         <span class="planned">Planned</span>          |
| Built-in meta management                        |                        Yes                        |        <span class="no">No <sup>1</sup></span>        |
| Page modal support                              | <span class="planned">Planned <sup>2</sup></span> |         <span class="planned">Planned</span>          |
| Request events support                          |                        Yes                        |                          Yes                          |
| Global events support                           |                        Yes                        |                          Yes                          |
| Internationalization support                    |            <span class="no">No</span>             |                [Yes](../guide/i18n.md)                |
| Property case conversion support                |            <span class="no">No</span>             |          [Yes](../guide/case-conversion.md)           |
| Exposed back-forward detection                  |            <span class="no">No</span>             |     [Yes](../api/composables/use-back-forward.md)     |
| Framework-agnostic core                         |                        Yes                        |                          Yes                          |
| Custom Axios instance support                   |            <span class="no">No</span>             |   [Yes](../api/utils/initialize-hybridly.md#axios)    |
| Official React, Svelte, Rails, Django support   |                        Yes                        |              <span class="no">No</span>               |

<div class="opacity-80">
  1. <a href="https://github.com/vueuse/head"><code>@vueuse/head</code></a> is recommended instead <br />
  2. Support through thrid-party package using <a href="https://github.com/lepikhinb/momentum-modal">Momentum</a>
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
