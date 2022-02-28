import { Options as LaravelOptions } from 'vite-plugin-laravel'

export interface Options extends LaravelOptions {
	/** Options for the layout plugin. */
	layout?: {
		/** The directory in which layouts are stored. */
		directory?: string
	}
}
