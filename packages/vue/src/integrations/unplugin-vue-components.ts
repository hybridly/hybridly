/**
 * Preset for `unplugin-auto-imports`.
 * @see https://github.com/antfu/unplugin-auto-imports
 */
export const HybridlyImports = {
	'hybridly/vue': [
		'useProperty',
		'useProperties',
		'useRouter',
		'useBackForward',
		'useContext',
		'useForm',
		'useHistoryState',
		'usePaginator',
		'defineLayout',
		'defineLayoutProperties',
		'route',
	],
	'hybridly': [
		'registerHook',
		'registerHookOnce',
		'router',
		'can',
	],
}
