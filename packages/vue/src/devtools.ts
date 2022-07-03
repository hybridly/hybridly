import { setupDevtoolsPlugin } from '@vue/devtools-api'
import { App, Plugin, triggerRef } from 'vue'
import { state } from './stores/state'

declare const __VUE_PROD_DEVTOOLS__: boolean

const monolikitStateType = 'monolikit'
const monolikitEventsTimelineLayerId = 'Monolikit'

export function setupDevtools(app: App) {
	setupDevtoolsPlugin({
		id: 'monolikit',
		label: 'Monolikit',
		packageName: '@monolikit/vue',
		homepage: 'https://github.com/monolikit',
		app: app as any,
		enableEarlyProxy: true,
		componentStateTypes: [
			monolikitStateType,
		],
	}, (api) => {
		/*
		|--------------------------------------------------------------------------
		| State
		|--------------------------------------------------------------------------
		*/
		api.on.inspectComponent((payload) => {
			payload.instanceData.state.push({
				type: monolikitStateType,
				key: 'properties',
				value: state.context.value?.view.properties,
				editable: true,
			})

			payload.instanceData.state.push({
				type: monolikitStateType,
				key: 'component',
				value: state.context.value?.view.name,
			})

			payload.instanceData.state.push({
				type: monolikitStateType,
				key: 'version',
				value: state.context.value?.version,
			})

			payload.instanceData.state.push({
				type: monolikitStateType,
				key: 'url',
				value: state.context.value?.url,
			})
		})

		// Updates the state on edition.
		api.on.editComponentState((payload) => {
			if (payload.type === monolikitStateType) {
				payload.set(state.context.value?.view)
			}
		})

		/*
		|--------------------------------------------------------------------------
		| Events
		|--------------------------------------------------------------------------
		*/
		api.addTimelineLayer({
			id: monolikitEventsTimelineLayerId,
			color: 0xFBC9E5,
			label: 'Monolikit',
		})

		const events = [
			'start',
			'data',
			'navigate',
			'progress',
			'error',
			'abort',
			'success',
			'invalid',
			'exception',
			'fail',
			'after',
		] as const

		state.context.value?.events.on('before', (options) => {
			const groupId = (Math.random() + 1).toString(36).substring(7)

			api.addTimelineEvent({
				layerId: monolikitEventsTimelineLayerId,
				event: {
					groupId,
					title: 'before',
					time: api.now(),
					data: options,
				},
			})

			events.forEach((event) => {
				const unregister = state.context.value?.events.on(event, (data: any) => {
					api.addTimelineEvent({
						layerId: monolikitEventsTimelineLayerId,
						event: {
							groupId,
							title: event,
							time: api.now(),
							data,
						},
					})

					if (event === 'after') {
						setTimeout(() => {
							triggerRef(state.context)
							api.notifyComponentUpdate()
						}, 100)
					}

					unregister?.()
				})
			})
		})
	})
}

export const plugin = <Plugin>{
	install(app) {
		if (process.env.NODE_ENV === 'development' || __VUE_PROD_DEVTOOLS__) {
			setupDevtools(app)
		}
	},
}