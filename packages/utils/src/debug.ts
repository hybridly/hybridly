import makeDebugger from 'debug'

export const debug = {
	router: makeDebugger('monolikit:core:router'),
	history: makeDebugger('monolikit:core:history'),
	url: makeDebugger('monolikit:core:url'),
	context: makeDebugger('monolikit:core:context'),
	external: makeDebugger('monolikit:core:external'),
	scroll: makeDebugger('monolikit:core:scroll'),
	hook: makeDebugger('monolikit:core:hook'),
	plugin: (name: string, ...args: any[]) => makeDebugger('monolikit:plugin').extend(name.replace('monolikit:', ''))(args.shift(), ...args),
	adapter: (name: string, ...args: any[]) => makeDebugger('monolikit:adapter').extend(name)(args.shift(), ...args),
}
