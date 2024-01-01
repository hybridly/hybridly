import icons from 'unplugin-icons/vite'
import { FileSystemIconLoader } from 'unplugin-icons/loaders'
import type { DynamicConfiguration } from '@hybridly/core'
import type { ViteOptions } from '../types'

type IconsOptions = Parameters<typeof icons>[0]

type CustomIconOptions = string[] | {
	/** Name of the icons directory under the root directory. */
	icons?: string
	/** Names of the custom icon collections that should be registered. */
	collections?: string[]
}

function getIconsOptions(options: ViteOptions, config: DynamicConfiguration): IconsOptions {
	if (options.icons === false) {
		return {}
	}

	const resolved = Array.isArray(options.customIcons)
		? { icons: undefined, collections: options.customIcons }
		: options.customIcons

	const customIconDirectoryName = resolved?.icons ?? 'icons'
	const customCollections = Object.fromEntries(resolved?.collections?.map((collection) => [
		collection, FileSystemIconLoader(`./${config.architecture.root_directory}/${customIconDirectoryName}/${collection}`),
	]) ?? [])

	return {
		autoInstall: true,
		customCollections,
		...options.icons,
	}
}

export { CustomIconOptions, IconsOptions, getIconsOptions, icons }
