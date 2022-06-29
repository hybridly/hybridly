import makeDebugger from 'debug'

export const debug = {
	router: makeDebugger('hybridly:core:router'),
	history: makeDebugger('hybridly:core:history'),
	url: makeDebugger('hybridly:core:url'),
	context: makeDebugger('hybridly:core:context'),
	external: makeDebugger('hybridly:core:external'),
	scroll: makeDebugger('hybridly:core:scroll'),
	event: makeDebugger('hybridly:core:event'),
	adapter: (name: string, ...args: any[]) => makeDebugger('hybridly:adapter').extend(name)(args.shift(), ...args),
}
