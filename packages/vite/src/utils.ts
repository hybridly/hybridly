import makeDebugger from 'debug'
import { isPackageExists } from 'local-pkg'
import { LAYOUT_PLUGIN_NAME, ROUTING_PLUGIN_NAME } from './constants'

export const debug = {
	router: makeDebugger(ROUTING_PLUGIN_NAME),
	layout: makeDebugger(LAYOUT_PLUGIN_NAME),
}

export function isPackageInstalled(name: string, paths: string[] = [process.cwd()]) {
	return isPackageExists(name, { paths })
}
