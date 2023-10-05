import type { Plugin } from 'vite'

// This plugin forces the process to exit, because it may
// hang in low-memory environments like CI or production
export function killSwitch(): Plugin {
	let _enabled = false
	return {
		name: 'hybridly:build:kill-switch',
		config: ({ mode }) => {
			if (mode === 'build') {
				_enabled = true
			}
		},
		buildEnd: (error: any) => {
			if (!_enabled) {
				return
			}

			if (error) {
				console.error('Error when bundling')
				console.error(error)
				process.exit(1)
			}
		},
		closeBundle: () => {
			if (!_enabled) {
				return
			}
			process.exit(0)
		},
	}
}
