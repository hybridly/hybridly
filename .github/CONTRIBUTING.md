# Contributing to Hybridly

## Repository setup

To develop locally, fork the Hybridly repository and clone it in your local machine. This is a monorepository using pnpm workspaces that contains multiple npm packages, as well as the Laravel adapter package.

1. Once cloned, you may run `pnpm i` in the root directory. This will install the dependencies of all sub-packages.
2. You may then run `pnpm build` in the root directory, which will build all npm packages in the right order.

If you are working on Hybridly itself, re-building is not necessary—you may juste write and run tests as you go.

If you are working on an application that should use your local version of Hybridly, run the following steps:

1. Run `pnpm i /path/to/main/hybridly/package` in the root directory of your project. For instance, if your cloned Hybridly repository is in `~/Code/hybridly`, the command will be `pnpm i ~/Code/hybridly/packages/hybridly`.
2. Edit your `composer.json` to add a reference to your local version of Hybridly. Here is an example:

```json
"repositories": [
		{
				"type": "path",
				"url": "/Users/<you>/Code/hybridly/packages/laravel"
		}
],
```
3. Run `composer require "hybridly/laravel:*"` to replace the version constraint in your `composer.json` and symlink the repository.

Any change you will make in the Laravel package will instantly be reflected in your application, but changes made to the npm packages will need to be re-built using `pnpm build`.
