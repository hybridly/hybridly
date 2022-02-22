<br>

<p align="center">
  <img src=".github/assets/logo-round.svg" style="width:125px;" />
</p>

<h1 align="center">Sleightful</h1>

<p align="center">
  <br />
  <a href="https://github.com/sleightful/sleightful/actions/workflows/test.yml"><img alt="Status" src="https://github.com/sleightful/sleightful/actions/workflows/test.yml/badge.svg"></a>
  <span>&nbsp;</span>
  <a href="https://github.com/sleightful/sleightful/releases"><img alt="version" src="https://img.shields.io/github/v/release/sleightful/sleightful?include_prereleases&label=version&logo=github&logoColor=white"></a>
  <br />
  <br />
  <pre><div align="center">npm i -D sleightful</div></pre>
</p>


<div align="center">
  <br />
  A mechanism to develop a server-driven, client-rendered applications.
  <br />
  Sleightful is an <a href="https://inertiajs.com">Inertia.js</a> fork which sole purpose is to try to push the developer experience to the maximum.
  <br />
  <b>Use at your own risk</b>.
  <br />
  <br />
  <br />
</div>

<br>

#   Features

It's basically [Inertia.js](https://inertiajs.com), but:

- it's specifically tailored for Vite, Vue 3 and Laravel
- it's written with TypeScript, so it's fully typed
- it has a different API, the core concepts stay the same
- it's distributed with a [Vite](https://vitejs.dev) plugin

# Installation

```sh
composer require sleightful/laravel
npm i -D sleightful
```

```ts
// vite.config.ts
import sleightful from 'sleightful/vite'

export default {
  plugins: [
    sleightful({ /* options */ })
  ]
}
```


<p align="center">
  <br />
  <br />
  ·
  <br />
  <br />
  <sub>Built with ❤︎ by <a href="https://github.com/enzoinnocenzi">Enzo Innocenzi</a>. <br/> Original credits go to <a href="https://reinink.ca">Jonathan Reinink</a>, the core team and the contributors.</sub>
</p>
