import { useForm, useProperty } from '@hybridly/vue'
import { mount } from '@vue/test-utils'
import { test, vi } from 'vitest'
import { defineComponent, nextTick, watch } from 'vue'
import { server } from '../../core/test/server'
import { fakeRouter, mockSuccessfulUrl } from '../../core/test/utils'
import { state } from '../../vue/src/stores/state'

test('it has no reactivity issues', async ({ expect }) => {
	type TestProperty = Array<{
		deep: {
			data1?: boolean
			data2: boolean
		}
	}>

	type ExposedComponent = {
		testProperty: TestProperty
		form: {
			submit: () => Promise<unknown>
		}
	}

	const initialTestProperty: TestProperty = [
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
	]

	const testData = {
		with: initialTestProperty,
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
		setup(_, { expose }) {
			const testProperty = useProperty<TestProperty>('test.with')

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
	const vm = wrapper.vm as unknown as ExposedComponent

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

	expect(vm.testProperty).toMatchObject(testData.with)
	await vm.form.submit()

	expect(testData.with).not.toMatchObject(vm.testProperty)
	expect(vm.testProperty[0].deep.data1).toBeTruthy()

	await nextTick()
	expect(watchFn).toBeCalledTimes(1)
})
