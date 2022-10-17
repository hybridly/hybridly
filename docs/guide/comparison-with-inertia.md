# Comparison with Inertia

## Features

The following is a non-exhaustive comparison table between Inertia and Hybridly's features.

|                                                 |                   Inertia                    |                Hybridly                 |
| ----------------------------------------------- | :------------------------------------------: | :-------------------------------------: |
| Link component                                  |                     Yes                      |                   Yes                   |
| Programmatic navigation                         |                     Yes                      |                   Yes                   |
| Back-end initiated external navigation support  |                     Yes                      |                   Yes                   |
| Front-end-initiated external navigation support |          <span class="no">No</span>          |                   Yes                   |
| Local navigation support (no server round-trip) |          <span class="no">No</span>          |                   Yes                   |
| Global properties                               |                     Yes                      |                   Yes                   |
| TypeScript support for global properties        |          <span class="no">No</span>          |                   Yes                   |
| Persistent properties                           |          <span class="no">No</span>          |                   Yes                   |
| Partial reloads                                 |                     Yes                      |                   Yes                   |
| Inclusion support for partials                  |                     Yes                      |                   Yes                   |
| Exclusion support for partials                  |          <span class="no">No</span>          |                   Yes                   |
| Dot notation support for partials               |          <span class="no">No</span>          |                   Yes                   |
| Persistent layout                               |                     Yes                      |                   Yes                   |
| Persistent layout properties                    |          <span class="no">No</span>          |                   Yes                   |
| Vue DevTools integration                        |          <span class="no">No</span>          |                   Yes                   |
| Vite integration                                |          <span class="no">No</span>          |                   Yes                   |
| `layout` support in templates                   |          <span class="no">No</span>          |                   Yes                   |
| Built-in `Paginator` types                      |          <span class="no">No</span>          |                   Yes                   |
| Built-in `form` util                            |                     Yes                      |                   Yes                   |
| Built-in `route` util with TypeScript support   |          <span class="no">No</span>          |                   Yes                   |
| Built-in `can` util with TypeScript support     |          <span class="no">No</span>          |                   Yes                   |
| Built-in testing utils                          |                     Yes                      |                   Yes                   |
| Auto-conversion to `FormData`                   |                     Yes                      |                   Yes                   |
| Infinite scroll support                         |          <span class="no">No</span>          |                   Yes                   |
| Server-side rendering                           |                     Yes                      |     <span class="no">Planned</span>     |
| Built-in meta management                        |                     Yes                      | <span class="no">No <sup>1</sup></span> |
| Page modal support                              | <span class="no">Planned <sup>2</sup></span> |     <span class="no">Planned</span>     |
| Events support                                  |                     Yes                      |                   Yes                   |
| Exposed back-forward detection                  |          <span class="no">No</span>          |                   Yes                   |
| Framework-agnostic core                         |                     Yes                      |                   Yes                   |
| Custom Axios instance support                   |          <span class="no">No</span>          |     <span class="no">Planned</span>     |
| Official React, Svelte, Rails, Django support   |                     Yes                      |       <span class="no">No</span>        |

<div class="opacity-80">
  1. <a href="https://github.com/vueuse/head"><code>@vueuse/head</code></a> is recommended instead <br />
  2. Support through thrid-party package using <a href="https://github.com/lepikhinb/momentum-modal">Momentum</a>
</div>

<style>
.no {
  opacity: .5;
}

tbody > tr > td {
  width: 100%;
}
</style>
