import { mount } from '@vue/test-utils'
import { vi } from 'vitest'
import { defineComponent, nextTick, watch } from 'vue'
import { useForm, useProperty } from '@hybridly/vue'
import { state } from '@hybridly/vue/stores/state'
import { fakeRouter, mockSuccessfulUrl } from '../../core/test/utils'
import { server } from '../../core/test/server'

test('it has no reactivity issues', async ({ expect }) => {
	const testData = {
		with: [
			{
				deep: {
					data2: true,
				},
			},
			{
				deep: {
					data2: true,
				},
			},
		],
	}
	const router = await fakeRouter({
		payload: {
			view: {
				properties: {
					test: testData,
				},
			},
		},
		adapter: {
			// TODO we need a better way to mock the adapter than to pick the logic from initialize.ts
			onContextUpdate: (context) => {
				state.setContext(context)
			},

			onViewSwap: async (options) => {
				state.setProperties(options.properties)
			},
		},
	})
	state.setContext(router)
	state.setProperties({
		test: testData,
	})

	const watchFn = vi.fn()
	const TestReactivityInComponent = defineComponent({
		setup(props, { expose }) {
			const testProperty = useProperty('test.with')

			watch(testProperty, watchFn)

			const form = useForm({
				url: 'http://localhost.test/navigation',
				method: 'POST',
				only: ['test'],
				fields: {},
			})

			expose({
				testProperty,
				form,
			})
		},
		template: '<div></div>',
	})
	const wrapper = mount(TestReactivityInComponent)

	server.resetHandlers(mockSuccessfulUrl('http://localhost.test/navigation', 'post', {}, {
		view: {
			component: 'default.view2',
			properties: {
				test: {
					with: [
						{
							deep: {
								data1: true,
								data2: true,
							},
						},
						{
							deep: {
								data1: false,
								data2: true,
							},
						},
					],
				},
			},
		},
	}))

	expect(wrapper.componentVM.testProperty).toMatchObject(testData.with)
	await wrapper.componentVM.form.submit()

	expect(testData.with).not.toMatchObject(wrapper.componentVM.testProperty)
	expect(wrapper.componentVM.testProperty[0].deep.data1).toBeTruthy()

	await nextTick()
	expect(watchFn).toBeCalledTimes(1)
})
