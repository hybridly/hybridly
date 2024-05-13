# Introduction

<div class="preface">
Hybridly's purpose is to drastically improve the productivity and the developer experience of writing interactive applications using Laravel and Vue.
</div>

## Overview

Using a protocol similar to the one [Jonathan Reinink](https://reinink.ca) invented for [Inertia](https://inertiajs.com), Hybridly makes it possible to build applications using Vue instead of Blade, while keeping the benefits of classic monolithic applications.

Hybridly is essentially very similar to Inertia, but it has a different philosophy. Since it focuses on Laravel, Vite and Vue instead of being completely framework-agnostic, it **has more built-in features** and **quality of life improvements**, which results in a **better developer experience** overall.

In other words, Hybridly is more like a framework built on top of Laravel and Vue, focusing specifically on being the perfect glue between the two.

:::info Differences with Inertia
To get an idea about their differences, head over to the [comparison page](../guide/comparison-with-inertia.md).
:::

## About Inertia and Hybridly

I was barely into the Laravel ecosystem when Jonathan Reinink was already looking for a way to [build Vue-powered Laravel applications](https://reinink.ca/articles/server-side-apps-with-client-side-rendering) the right way.

He came up with Inertia, which is now backed by Laravel. It powers [Forge](https://forge.laravel.com). It is a well-established tool. If you already build applications using Inertia and you don't feel like you should change your stack, there is no need to reach for a different tool.

**However, Inertia has its issues**.

The pace of development of Inertia has been a source of frustration for its users. 

There have been months without release or news about its development. Months without any commit to the repository. Months during which pull requests and issues were not handled, and are, to this day, still not addressed.

Because of that, other issues with the implementation itself, and some of my opinions diverging from the philosophy of the maintainers, I simply decided to build my own solution.

---

<!--@include: @/../README.md{10,32}-->
