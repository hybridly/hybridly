# `useContext`

This composable returns `ComputedRef` of the entire context of the current Hybridly instance. 

It is meant as an escape-hatch for advanced use cases, and not for your typical application.

## Usage

```ts
interface RouterContext {
	/** The current, normalized URL. */
	url: string
	/** The current component's name. */
	view: View
	/** The current local asset version. */
	version: string
	/** The current adapter's functions. */
	adapter: Adapter
	/** Scroll positions of the current page's DOM elements. */
	scrollRegions: ScrollRegion[]
	/** Arbitrary state. */
	state: Record<string, any>
	/** Currently pending navigation. */
	pendingNavigation?: PendingNavigation
	/** History state serializer. */
	serializer: Serializer
	/** List of plugins. */
	plugins: Plugin[]
	/** Global hooks. */
	hooks: Partial<Record<keyof Hooks, Array<Function>>>
}

function useContext(): RouterContext
```
