/**
 * Preset for `unplugin-auto-imports`.
 * @see https://github.com/antfu/unplugin-auto-imports
 */
export const HybridlyImports = {
	'hybridly/vue': [
		'useProperty',
		'useTypedProperty',
		'useProperties',
		'useRouter',
		'useBackForward',
		'useContext',
		'useForm',
		'useHistoryState',
		'usePaginator',
		'defineLayout',
		'defineLayoutProperties',
	],
	'hybridly': [
		'registerHook',
		'router',
		'route',
		'can',
	],
}
