import makeDebugger from 'debug'
import { LAYOUT_PLUGIN_NAME, ROUTING_PLUGIN_NAME } from './constants'

export const debug = {
	router: makeDebugger(ROUTING_PLUGIN_NAME),
	layout: makeDebugger(LAYOUT_PLUGIN_NAME),
}
