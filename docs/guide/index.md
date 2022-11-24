# What is Hybridly?

## Overview

Hybridly is essentially a fork of [Inertia](https://inertiajs.com). If you already know Inertia, you also know Hybridly. The difference resides in its philosophy and the resulting developer experience.

If you didn't know about Inertia, Hybridly is a set of tools that implements a protocol for building monolithic, server-driven, client-rendered web application.

In other words, you can build single-page applications powered b**y** a classic back-end, without an API, client-side routing, or any of the usual complexity involved in a SPA.

## About Inertia

First of all, credits go to [Jonathan Reinink](https://reinink.ca) for the idea behind Inertia. I was barely into the Laravel ecosystem when [he was already looking for a way to build Vue-powered Laravel applications the right way](https://reinink.ca/articles/server-side-apps-with-client-side-rendering).

Inertia is backed by Laravel, that uses it for [Forge](https://forge.laravel.com). It is a well-established tool. If you already build applications using Inertia and you don't feel like you should change your stack, there is no need to reach for a different tool.

**However, Inertia has its issues**.

Its pace of development has been a source of frustration for its users. There have been months without release or news about its development. Months without any commit to the repository. Months within which pull requests and issues were not handled.

Because of that, a few other issues with the implementation itself, and some of my opinions diverging from the philosophy of the maintainers, I simply decided to build my own solution.

---

:::info Credits where due
Part of Hybridly's code as well as part of this documentation is inspired by, if not taken from, Inertia's codebase and own documentation.
:::

## Questions & answers

**What's the goal of this project?**
> Hybridly aims to provide the best developer experience possible when using Laravel, Vue and Vite. Over time, it might become closer to what Nuxt 3 currently is in terms of DX.

**When should I use Hybridly instead of Inertia?**
> Inertia is popular and sponsored by Laravel. It's the safe option. Hybridly is moving faster, and exists because Inertia has issues and a different philosophy. Chose Inertia for better community supoport, and Hybridly if you value developer experience more at the expense of a smaller community.

**Why fork Inertia instead of contributing?**
> That's what I tried before writing Hybridly, but the maintenance of Inertia is highly lacking, pull requests and issues are not being addressed. Additionally, its minimalist philosophy is not compatible with my developer experience needs.

**Can I use Hybridly with other frameworks than Laravel or Vue?**
> The core of Hybridly is framework-agnostic, just like Inertia's. But there is no plan for an official adapter other than Laravel and Vue, because that is what I am using and willing to maintain. Feel free to create your own adapter though.

**Will Hybridly be properly maintained?**
> I'm primarily building Hybridly for myself. I am actively using and improving it. That means Hybridly is an opinionated project and may not suit your tastes, but it will live as long as I am a developer and I didn't find a better way to build web applications.
