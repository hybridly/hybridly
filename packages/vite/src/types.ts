export interface Options {
	/** Options for the layout plugin. */
	layout?: false | LayoutOptions

	/** Options for the router plugin. */
	router?: false | RouterOptions
}

export interface LayoutOptions {
	/** The directory in which layouts are stored. */
	directory?: string
	/** Function that resolves the layout path given its name. */
	resolve?: (layout: string) => string
}

export interface RouterOptions {
	/** Path to the PHP executable. */
	php?: string
	/** Path to definition file. */
	dts?: false | string
	/** File patterns to watch. */
	watch?: RegExp[]
}
