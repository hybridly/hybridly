import { setupDevtoolsPlugin } from '@vue/devtools-api'
import { events } from '@hybridly/core'
import { App, Plugin, triggerRef } from 'vue'
import { state } from './stores/state'

declare const __VUE_PROD_DEVTOOLS__: boolean

const hybridlyStateType = 'hybridly'
const hybridlyEventsTimelineLayerId = 'Hybridly'

export function setupDevtools(app: App) {
	setupDevtoolsPlugin({
		id: 'hybridly',
		label: 'Hybridly',
		packageName: '@hybridly/vue',
		homepage: 'https://github.com/hybridly',
		app: app as any,
		enableEarlyProxy: true,
		componentStateTypes: [
			hybridlyStateType,
		],
	}, (api) => {
		/*
		|--------------------------------------------------------------------------
		| State
		|--------------------------------------------------------------------------
		*/
		api.on.inspectComponent((payload) => {
			payload.instanceData.state.push({
				type: hybridlyStateType,
				key: 'properties',
				value: state.context.value?.view.properties,
				editable: true,
			})

			payload.instanceData.state.push({
				type: hybridlyStateType,
				key: 'component',
				value: state.context.value?.view.name,
			})

			payload.instanceData.state.push({
				type: hybridlyStateType,
				key: 'version',
				value: state.context.value?.version,
			})

			payload.instanceData.state.push({
				type: hybridlyStateType,
				key: 'url',
				value: state.context.value?.url,
			})

			payload.instanceData.state.push({
				type: hybridlyStateType,
				key: 'router',
				value: state.routes.value,
			})
		})

		// Updates the state on edition.
		api.on.editComponentState((payload) => {
			if (payload.type === hybridlyStateType) {
				payload.set(state.context.value?.view)
			}
		})

		/*
		|--------------------------------------------------------------------------
		| Events
		|--------------------------------------------------------------------------
		*/
		api.addTimelineLayer({
			id: hybridlyEventsTimelineLayerId,
			color: 0xFBC9E5,
			label: 'Hybridly',
		})

		const listen = [
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

		events.before.on((options) => {
			const groupId = (Math.random() + 1).toString(36).substring(7)

			api.addTimelineEvent({
				layerId: hybridlyEventsTimelineLayerId,
				event: {
					groupId,
					title: 'before',
					time: api.now(),
					data: options,
				},
			})

			listen.forEach((event) => events[event].once((data: any) => {
				api.addTimelineEvent({
					layerId: hybridlyEventsTimelineLayerId,
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
			}))
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
