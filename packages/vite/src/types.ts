export interface Options {
	/** Options for the layout plugin. */
	layout?: false | LayoutOptions
}

export interface LayoutOptions {
	/** The directory in which layouts are stored. */
	directory?: string
}
