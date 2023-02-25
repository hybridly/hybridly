import type { ResolvedHybridlyConfig } from '@hybridly/config'
import icons from 'unplugin-icons/vite'
import { FileSystemIconLoader } from 'unplugin-icons/loaders'
import type { ViteOptions } from '../types'

type IconsOptions = Parameters<typeof icons>[0]

interface CustomIconOptions {
	/** Name of the icons directory under the root directory. */
	icons?: string
	/** Names of the custom icon collections that should be registered. */
	collections?: string[]
}

function getIconsOptions(options: ViteOptions, config: ResolvedHybridlyConfig): IconsOptions {
	if (options.icons === false) {
		return {}
	}

	const customIconDirectoryName = options.customIcons?.icons ?? 'icons'
	const customCollections = Object.fromEntries(options?.customIcons?.collections?.map((collection) => [
		collection, FileSystemIconLoader(`./${config.root}/${customIconDirectoryName}/${collection}`),
	]) ?? [])

	return {
		autoInstall: true,
		customCollections,
		...options.icons,
	}
}

export { CustomIconOptions, IconsOptions, getIconsOptions, icons }
