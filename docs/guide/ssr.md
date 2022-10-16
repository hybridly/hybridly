# Server-side rendering

## On the roadmap

Hybridly does not currently support server-side rendering, though we aim to provide first-class support for it in the future. 

The intent is to provide a single development server instead of both the Vite server and the SSR one.

## Is it really useful?

While server-side rendering has benefits, it also has its share of drawbacks. Before looking into implementing it, you should ask yourself if your project really needs it. 

The main reason one would want to implement server-side rendering is to benefit from search engine optimization. However, Hybridly is primarily meant to power interaction-rich single-page applications - the kind of application that is not usually critically dependant on search engine optimization. 

If your application is not rich in interactions, maybe [Livewire](https://laravel-livewire.com) is a better fit for you.

## How indexing works

It is worthy of noting that client-side-rendered applications are still indexed by Google. 

Hybridly applications without server-side rendering use the [application shell model](https://web.dev/learn/pwa/architecture/), where the HTML doesn't contain actual content and JavaScript needs to be executed.

[Google does crawl such applications](https://developers.google.com/search/docs/crawling-indexing/javascript/javascript-seo-basics#how-googlebot-processes-javascript), but it puts them on a render queue. An application on Google's render queue can take a few seconds to a few days to be crawled and indexed, depending on Google's resources at the moment.

If your application is not *critically* dependant on search engine optimization, you probably don't need server-side rendering.
