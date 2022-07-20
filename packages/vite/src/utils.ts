import makeDebugger from 'debug'
import { LAYOUT_PLUGIN_NAME, ROUTER_PLUGIN_NAME } from './constants'

export const debug = {
	router: makeDebugger(ROUTER_PLUGIN_NAME),
	layout: makeDebugger(LAYOUT_PLUGIN_NAME),
}
