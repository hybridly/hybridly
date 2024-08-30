import makeDebugger from 'debug'

export const debug = {
	router: makeDebugger('hybridly:core:router'),
	history: makeDebugger('hybridly:core:history'),
	url: makeDebugger('hybridly:core:url'),
	context: makeDebugger('hybridly:core:context'),
	external: makeDebugger('hybridly:core:external'),
	scroll: makeDebugger('hybridly:core:scroll'),
	hook: makeDebugger('hybridly:core:hook'),
	queue: makeDebugger('hybridly:core:queue'),
	plugin: (name: string, ...args: any[]) => makeDebugger('hybridly:plugin').extend(name.replace('hybridly:', ''))(args.shift(), ...args),
	adapter: (name: string, ...args: any[]) => makeDebugger('hybridly:adapter').extend(name)(args.shift(), ...args),
}
