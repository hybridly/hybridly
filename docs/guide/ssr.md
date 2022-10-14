# Server-side rendering

## On the roadmap

Hybridly does not currently support server-side rendering, though we aim to provide first-class support for it in the future. 

The intent is to provide a single development server instead of both the Vite server and the SSR one.

## Is it really useful?

While server-side rendering has benefits, it also has its share of drawbacks. Before looking into implementing it, you should ask yourself if your project really needs it. 

The main reason one would want to implement server-side rendering is to benefit from search engine optimization. However, Hybridly is primarily meant to power interaction-rich single-page applications - the kind of application that is not usually critically dependant on search engine optimization. 

If your application is not rich in interactions, maybe [Livewire](https://laravel-livewire.com) is a better fit for you.

:::info Google indexes client-side-rendered applications
It is worthy of noting that client-side-rendered applications are also indexed by Google. It simply takes longer: up to one week, usually a few hours. If your application is not *critically* dependant on search engine optimization, you probably don't need server-side rendering.
:::
