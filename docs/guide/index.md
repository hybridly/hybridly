# What is Hybridly?

## Overview

Hybridly is essentially a fork of [Inertia](https://inertiajs.com). If you already know Inertia, you also know Hybridly. The difference resides in its philosophy and the resulting developer experience.

If you didn't know about Inertia, Hybridly is a set of tools that implements a protocol for building monolithic, server-driven, client-rendered web application.

In other words, you can build single-page applications powered by a classic back-end, without an API, client-side routing, or any of the usual complexity involved in a SPA.

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
