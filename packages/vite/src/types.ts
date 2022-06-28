import { Options as LaravelOptions } from 'vite-plugin-laravel'

export interface Options extends LaravelOptions {
	/** Options for the layout plugin. */
	layout?: false | LayoutOptions
}

export interface LayoutOptions {
	/** The directory in which layouts are stored. */
	directory?: string
}
