import { router } from '@hybridly/core'
import { Form, useForm } from '@hybridly/vue'
import { mount } from '@vue/test-utils'
import { beforeEach, test, vi } from 'vitest'
import { defineComponent, nextTick } from 'vue'
import { server } from '../../core/test/server'
import { delay, fakeRouterContext, mockInvalidUrl, mockSuccessfulUrl } from '../../core/test/utils'

beforeEach(async () => {
	await fakeRouterContext({
		adapter: {
			executeOnMounted: (callback) => callback(),
		},
	})
})

test('it resets dirty state after successful form submission', async ({ expect }) => {
	// Given
	server.resetHandlers(mockSuccessfulUrl('http://localhost.test/navigation', 'post'))

	const form = useForm({
		url: 'http://localhost.test/navigation',
		fields: {
			foo: 'bar',
		},
	})

	// Ensure form is dirty when changed
	form.fields.foo = 'baz'
	await nextTick()
	expect(form.isDirty).toBe(true)

	// When
	await form.submit()

	// Then
	await nextTick()
	expect(form.isDirty).toBe(false)
})

test('it does not reset dirty state after failed form submission', async ({ expect }) => {
	// Given
	server.resetHandlers(mockInvalidUrl('http://localhost.test/navigation', 'post'))

	const form = useForm({
		url: 'http://localhost.test/navigation',
		fields: {
			foo: 'bar',
		},
	})

	// Ensure form is dirty when changed
	form.fields.foo = 'baz'
	await nextTick()
	expect(form.isDirty).toBe(true)

	// When
	await form.submit()

	// Then
	await nextTick()
	expect(form.isDirty).toBe(true)
})

test('it updates failed and successful', async ({ expect }) => {
	server.resetHandlers(mockSuccessfulUrl('http://localhost.test/navigation', 'post'))

	const form = useForm({
		url: 'http://localhost.test/navigation',
		timeout: 10,
		fields: {},
		resetOnSuccess: false,
	})

	expect(form.successful).toBe(false)
	expect(form.recentlySuccessful).toBe(false)
	expect(form.failed).toBe(false)
	expect(form.recentlyFailed).toBe(false)
	expect(form.processing).toBe(false)

	await form.submit({
		hooks: {
			start() {
				expect(form.processing).toBe(true)
			},
		},
	})

	expect(form.successful).toBe(true)
	expect(form.recentlySuccessful).toBe(true)
	expect(form.failed).toBe(false)
	expect(form.recentlyFailed).toBe(false)
	expect(form.processing).toBe(false)

	await delay(10)
	expect(form.successful).toBe(true)
	expect(form.recentlySuccessful).toBe(false)

	form.resetSubmissionState()
	expect(form.successful).toBe(false)

	await form.submit() // Retrigger submission to have successful = true

	expect(form.successful).toBe(true)

	server.resetHandlers(mockInvalidUrl('http://localhost.test/navigation', 'post'))

	await form.submit({
		hooks: {
			before() {
				expect(form.successful).toBe(false)
			},
		},
	})

	expect(form.successful).toBe(false)
	expect(form.recentlySuccessful).toBe(false)
	expect(form.failed).toBe(true)
	expect(form.recentlyFailed).toBe(true)

	await delay(10)
	expect(form.failed).toBe(true)
	expect(form.recentlyFailed).toBe(false)

	form.resetSubmissionState()
	expect(form.failed).toBe(false)
})

test('it can override all options', async ({ expect }) => {
	// Given
	server.resetHandlers(mockSuccessfulUrl('http://localhost.test/navigation', 'post'))

	const notToCall = vi.fn()
	const toCall = vi.fn()
	const form = useForm({
		setDefaultOnSuccess: true,
		resetOnSuccess: false,
		url: notToCall,
		method: 'PATCH',
		fields: {
			foo: 'bar',
		},
		hooks: {
			success: notToCall,
			after: toCall,
		},
		transform: notToCall,
	})

	// Ensure form is dirty when changed
	form.fields.foo = 'baz'
	await nextTick()

	// When
	await form.submit({
		url: () => 'http://localhost.test/navigation',
		method: 'POST',
		setDefaultOnSuccess: false,
		resetOnSuccess: true,
		hooks: {
			success: toCall,
		},
		transform: toCall,
	})

	// Then
	await nextTick()
	expect(form.fields.foo).toBe('bar')
	expect(notToCall).toBeCalledTimes(0)
	expect(toCall).toBeCalledTimes(3)
})

test('it resets only selected fields on successful useForm submission', async ({ expect }) => {
	server.resetHandlers(mockSuccessfulUrl('http://localhost.test/navigation', 'post'))

	const form = useForm({
		url: 'http://localhost.test/navigation',
		resetOnSuccess: ['name'],
		fields: {
			name: 'Fern',
			title: 'Mage',
		},
	})

	form.fields.name = 'Frieren'
	form.fields.title = 'Sorcerer'

	await form.submit()

	expect(form.fields.name).toBe('Fern')
	expect(form.fields.title).toBe('Sorcerer')
})

test('it resets only selected fields on failed useForm submission', async ({ expect }) => {
	server.resetHandlers(mockInvalidUrl('http://localhost.test/navigation', 'post'))

	const form = useForm({
		url: 'http://localhost.test/navigation',
		resetOnSuccess: false,
		resetOnError: ['name'],
		fields: {
			name: 'Fern',
			title: 'Mage',
		},
	})

	form.fields.name = 'Frieren'
	form.fields.title = 'Sorcerer'

	await form.submit()

	expect(form.fields.name).toBe('Fern')
	expect(form.fields.title).toBe('Sorcerer')
})

test('it can set defaults only for selected useForm fields on success', async ({ expect }) => {
	server.resetHandlers(mockSuccessfulUrl('http://localhost.test/navigation', 'post'))

	const form = useForm({
		url: 'http://localhost.test/navigation',
		resetOnSuccess: false,
		setDefaultOnSuccess: ['name'],
		fields: {
			name: 'Fern',
			title: 'Mage',
		},
	})

	form.fields.name = 'Frieren'
	form.fields.title = 'Sorcerer'

	await form.submit()

	form.fields.name = 'Flamme'
	form.fields.title = 'Archmage'
	form.resetFields()

	expect(form.fields.name).toBe('Frieren')
	expect(form.fields.title).toBe('Mage')
})

test('it submits nested data from native form inputs', async ({ expect }) => {
	server.resetHandlers(mockSuccessfulUrl('http://localhost.test/users', 'post'))

	const navigateSpy = vi.spyOn(router, 'navigate')

	const TestComponent = defineComponent({
		components: { Form },
		template: `
			<Form action="http://localhost.test/users" method="post">
				<input type="text" name="user.name" value="John Doe" />
				<input type="text" name="user.skills[]" value="JavaScript" />
				<input type="text" name="address.street" value="123 Main St" />
				<button type="submit">Submit</button>
			</Form>
		`,
	})

	const wrapper = mount(TestComponent)

	await wrapper.find('form').trigger('submit')
	await nextTick()

	const [options] = navigateSpy.mock.calls.at(0) ?? []

	expect(options?.data).toEqual({
		user: {
			name: 'John Doe',
			skills: ['JavaScript'],
		},
		address: {
			street: '123 Main St',
		},
	})
})

test('it exposes slot utilities and disables form while processing', async ({ expect }) => {
	server.resetHandlers(mockSuccessfulUrl('http://localhost.test/users', 'post'))

	const navigateSpy = vi.spyOn(router, 'navigate')
	const startHook = vi.fn(async () => {
		await delay(80)
	})

	const TestComponent = defineComponent({
		components: { Form },
		setup() {
			return {
				startHook,
			}
		},
		template: `
			<Form
				action="http://localhost.test/users"
				method="post"
				disable-while-processing
				:options="{ preserveScroll: true, hooks: { start: startHook } }"
				v-slot="{ submit, processing }"
			>
				<input type="text" name="user.name" value="John Doe" />
				<button id="submit" type="button" @click="submit()">Submit</button>
				<span id="processing">{{ processing }}</span>
			</Form>
		`,
	})

	const wrapper = mount(TestComponent)

	await wrapper.find('#submit').trigger('click')

	await vi.waitFor(() => {
		expect(wrapper.find('form').attributes('inert')).toBe('')
	})

	await vi.waitFor(() => {
		expect(wrapper.find('form').attributes('inert')).toBeUndefined()
	})

	const [options] = navigateSpy.mock.calls.at(0) ?? []

	expect(options?.preserveScroll).toBe(true)
	expect(startHook).toBeCalledTimes(1)
})

test('it resets native controls through slot reset helper', async ({ expect }) => {
	const TestComponent = defineComponent({
		components: { Form },
		template: `
			<Form action="http://localhost.test/users" method="post" v-slot="{ reset }">
				<input id="spell" type="text" name="spell_name" value="Zoltraak" />
				<button id="reset" type="button" @click="reset()">Reset</button>
			</Form>
		`,
	})

	const wrapper = mount(TestComponent)
	const input = wrapper.find('#spell')

	await input.setValue('Flamme')
	expect((input.element as HTMLInputElement).value).toBe('Flamme')

	await wrapper.find('#reset').trigger('click')
	await nextTick()

	expect((wrapper.find('#spell').element as HTMLInputElement).value).toBe('Zoltraak')
})

test('it can set defaults on success before reset-on-success', async ({ expect }) => {
	server.resetHandlers(mockSuccessfulUrl('http://localhost.test/users', 'post'))

	const TestComponent = defineComponent({
		components: { Form },
		template: `
			<Form
				action="http://localhost.test/users"
				method="post"
				:set-default-on-success="true"
				:reset-on-success="true"
				v-slot="{ submit }"
			>
				<input id="spell" type="text" name="spell_name" value="Zoltraak" />
				<button id="submit" type="button" @click="submit()">Submit</button>
			</Form>
		`,
	})

	const wrapper = mount(TestComponent)
	const input = wrapper.find('#spell')

	await input.setValue('Flamme')
	await wrapper.find('#submit').trigger('click')
	await delay(20)

	expect((wrapper.find('#spell').element as HTMLInputElement).value).toBe('Flamme')
})

test('it can set defaults only for selected fields on success', async ({ expect }) => {
	server.resetHandlers(mockSuccessfulUrl('http://localhost.test/users', 'post'))

	const TestComponent = defineComponent({
		components: { Form },
		template: `
			<Form
				action="http://localhost.test/users"
				method="post"
				:set-default-on-success="['spell_name']"
				:reset-on-success="true"
				v-slot="{ submit }"
			>
				<input id="name" type="text" name="spell_name" value="Zoltraak" />
				<input id="type" type="text" name="spell_type" value="Offensive" />
				<button id="submit" type="button" @click="submit()">Submit</button>
			</Form>
		`,
	})

	const wrapper = mount(TestComponent)

	await wrapper.find('#name').setValue('Flamme')
	await wrapper.find('#type').setValue('Fire')
	await wrapper.find('#submit').trigger('click')
	await delay(20)

	expect((wrapper.find('#name').element as HTMLInputElement).value).toBe('Flamme')
	expect((wrapper.find('#type').element as HTMLInputElement).value).toBe('Offensive')
})

test('it can reset only selected fields on success', async ({ expect }) => {
	server.resetHandlers(mockSuccessfulUrl('http://localhost.test/users', 'post'))

	const TestComponent = defineComponent({
		components: { Form },
		template: `
			<Form
				action="http://localhost.test/users"
				method="post"
				:reset-on-success="['spell_name']"
				v-slot="{ submit }"
			>
				<input id="name" type="text" name="spell_name" value="Zoltraak" />
				<input id="type" type="text" name="spell_type" value="Offensive" />
				<button id="submit" type="button" @click="submit()">Submit</button>
			</Form>
		`,
	})

	const wrapper = mount(TestComponent)

	await wrapper.find('#name').setValue('Flamme')
	await wrapper.find('#type').setValue('Fire')
	await wrapper.find('#submit').trigger('click')
	await delay(20)

	expect((wrapper.find('#name').element as HTMLInputElement).value).toBe('Zoltraak')
	expect((wrapper.find('#type').element as HTMLInputElement).value).toBe('Fire')
})

test('it supports convenience props and merges with options', async ({ expect }) => {
	server.resetHandlers(mockSuccessfulUrl('http://localhost.test/users', 'post'))

	const navigateSpy = vi.spyOn(router, 'navigate')

	const TestComponent = defineComponent({
		components: { Form },
		template: `
			<Form
				action="http://localhost.test/users"
				method="post"
				error-bag="spell_discovery"
				:show-progress="false"
				:options="{ preserveScroll: true }"
				v-slot="{ submit }"
			>
				<input id="spell" type="text" name="spell_name" value="Zoltraak" />
				<button id="submit" type="button" @click="submit()">Submit</button>
			</Form>
		`,
	})

	const wrapper = mount(TestComponent)

	await wrapper.find('#submit').trigger('click')
	await delay(20)

	const [options] = navigateSpy.mock.calls.at(0) ?? []

	expect(options?.errorBag).toBe('spell_discovery')
	expect(options?.progress).toBe(false)
	expect(options?.preserveScroll).toBe(true)
})

test('it exposes getError with dot notation in slot', async ({ expect }) => {
	const TestComponent = defineComponent({
		components: { Form },
		template: `
			<Form action="http://localhost.test/users" method="post" v-slot="{ setErrors, getError }">
				<button id="set" type="button" @click="setErrors({ user: { name: 'Required' } })">Set</button>
				<span id="error">{{ getError('user.name') }}</span>
			</Form>
		`,
	})

	const wrapper = mount(TestComponent)

	await wrapper.find('#set').trigger('click')
	await nextTick()

	expect(wrapper.find('#error').text()).toBe('Required')
})

test('it keeps field values after validation errors', async ({ expect }) => {
	server.resetHandlers(mockInvalidUrl('http://localhost.test/users', 'post'))

	const TestComponent = defineComponent({
		components: { Form },
		template: `
			<Form action="http://localhost.test/users" method="post" v-slot="{ submit }">
				<input id="spell" type="text" name="spell_name" value="Zoltraak" />
				<button id="submit" type="button" @click="submit()">Submit</button>
			</Form>
		`,
	})

	const wrapper = mount(TestComponent)
	const input = wrapper.find('#spell')

	await input.setValue('Flamme')
	await wrapper.find('#submit').trigger('click')
	await delay(20)

	expect((wrapper.find('#spell').element as HTMLInputElement).value).toBe('Flamme')
})

test('it can reset only selected fields after validation errors', async ({ expect }) => {
	server.resetHandlers(mockInvalidUrl('http://localhost.test/users', 'post'))

	const TestComponent = defineComponent({
		components: { Form },
		template: `
			<Form
				action="http://localhost.test/users"
				method="post"
				:reset-on-error="['spell_name']"
				v-slot="{ submit }"
			>
				<input id="name" type="text" name="spell_name" value="Zoltraak" />
				<input id="type" type="text" name="spell_type" value="Offensive" />
				<button id="submit" type="button" @click="submit()">Submit</button>
			</Form>
		`,
	})

	const wrapper = mount(TestComponent)

	await wrapper.find('#name').setValue('Flamme')
	await wrapper.find('#type').setValue('Fire')
	await wrapper.find('#submit').trigger('click')
	await delay(20)

	expect((wrapper.find('#name').element as HTMLInputElement).value).toBe('Zoltraak')
	expect((wrapper.find('#type').element as HTMLInputElement).value).toBe('Fire')
})
