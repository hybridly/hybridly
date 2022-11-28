export interface Options {
	/** Options for the layout plugin. */
	layout?: false | LayoutOptions

	/** Options for the router plugin. */
	router?: false | RouterOptions
}

export interface LayoutOptions {
	/** Name of the layout used when no argument is provided to `layout`. */
	defaultLayoutName?: string
	/** Custom RegExp for parsing the template string. */
	templateRegExp?: RegExp
	/** The directory in which layouts are stored. */
	directory?: string
	/** Function that resolves the layout path given its name. */
	resolve?: (layout: string, domain?: string) => string
}

export interface RouterOptions {
	/** Path to the PHP executable. */
	php?: string
	/** Path to definition file. */
	dts?: false | string
	/** File patterns to watch. */
	watch?: RegExp[]
}
