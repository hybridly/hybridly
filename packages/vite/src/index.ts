import { Plugin } from 'vite'
import type { Options } from './types'

function plugin(options?: Options): Plugin {
	return {
		name: 'vite:sleightful',
	}
}

export { Options }
export default plugin
