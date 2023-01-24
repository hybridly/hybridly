/**
 * Preset for `unplugin-auto-imports`.
 * @see https://github.com/antfu/unplugin-auto-imports
 */
export const HybridlyImports = {
	'hybridly/vue': [
		'useProperty',
		'useTypedProperty',
		'useProperties',
		'useBackForward',
		'useContext',
		'useForm',
		'useDialog',
		'useHistoryState',
		'usePaginator',
		'defineLayout',
		'defineLayoutProperties',
		'registerHook',
	],
	'hybridly': [
		'router',
		'route',
		'current',
		'can',
	],
}
