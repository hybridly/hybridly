import makeDebugger from 'debug'

export const debug = {
	router: makeDebugger('monolikit:core:router'),
	history: makeDebugger('monolikit:core:history'),
	url: makeDebugger('monolikit:core:url'),
	context: makeDebugger('monolikit:core:context'),
	external: makeDebugger('monolikit:core:external'),
	scroll: makeDebugger('monolikit:core:scroll'),
	event: makeDebugger('monolikit:core:event'),
	adapter: (name: string, ...args: any[]) => makeDebugger('monolikit:adapter').extend(name)(args.shift(), ...args),
}
