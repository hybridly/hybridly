import type { Plugin } from 'vite'
import type { Options } from '@sleightful/vite'
import sleightfully from '@sleightful/vite'

export * from '@sleightful/vite'

export default function(options?: Options): Plugin[] {
	return [
		sleightfully(options),
	]
}
