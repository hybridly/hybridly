import { loadEnv } from 'vite'
import { type Plugin } from 'vite'
import { CONFIG_VIRTUAL_MODULE_ID } from '../constants'
import { getClientCode } from './client'

const LOCAL_BUILD_VIRTUAL_ID = 'virtual:hybridly/local-build'
const RESOLVED_LOCAL_BUILD_VIRTUAL_ID = `\0${LOCAL_BUILD_VIRTUAL_ID}`

export function warnOnLocalBuilds(): Plugin {
	let shouldDisplayWarning = false

	return {
		name: 'vite:hybridly:local-build',
		enforce: 'pre',
		apply: 'build',
		config(config, { mode }) {
			const env = loadEnv(mode, config.envDir ?? process.cwd(), '')
			shouldDisplayWarning = env.APP_ENV === 'local'
		},
		async resolveId(id: string) {
			if (!shouldDisplayWarning) {
				return
			}

			if (id === LOCAL_BUILD_VIRTUAL_ID) {
				return RESOLVED_LOCAL_BUILD_VIRTUAL_ID
			}
		},
		async load(id) {
			if (!shouldDisplayWarning) {
				return
			}

			if (id === RESOLVED_LOCAL_BUILD_VIRTUAL_ID) {
				return getClientCode()
			}
		},
		transform(code, id) {
			if (!shouldDisplayWarning) {
				return
			}

			if (!id.endsWith(CONFIG_VIRTUAL_MODULE_ID)) {
				return
			}

			code = `${code}\nimport '${LOCAL_BUILD_VIRTUAL_ID}'`
			return code
		},
	}
}
