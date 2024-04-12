import { beforeEach, it, vi } from 'vitest'
import { useForm } from '@hybridly/vue'
import { nextTick } from 'vue'
import { fakeRouterContext, mockSuccessfulUrl, mockInvalidUrl, delay } from '../../core/test/utils'
import { server } from '../../core/test/server'

beforeEach(async() => {
	await fakeRouterContext()
})

it('it resets dirty state after successful form submission', async({ expect }) => {
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

it('it does not reset dirty state after failed form submission', async({ expect }) => {
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

it('it updates failed and successful', async({ expect }) => {
	server.resetHandlers(mockSuccessfulUrl('http://localhost.test/navigation', 'post'))

	const form = useForm({
		url: 'http://localhost.test/navigation',
		timeout: 10,
		fields: {
		},
		reset: false,
	})

	expect(form.successful).toBe(false)
	expect(form.recentlySuccessful).toBe(false)
	expect(form.failed).toBe(false)
	expect(form.recentlyFailed).toBe(false)
	expect(form.processing).toBe(false)

	await form.submitWith({
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

	form.reset()
	expect(form.successful).toBe(false)

	await form.submit() // Retrigger submission to have successful = true

	expect(form.successful).toBe(true)

	server.resetHandlers(mockInvalidUrl('http://localhost.test/navigation', 'post'))

	await form.submitWith({
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

	form.reset()
	expect(form.failed).toBe(false)
})

it('it can override all options', async({ expect }) => {
	// Given
	server.resetHandlers(mockSuccessfulUrl('http://localhost.test/navigation', 'post'))

	const notToCall = vi.fn()
	const toCall = vi.fn()
	const form = useForm({
		updateInitials: true,
		reset: false,
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
	await form.submitWith({
		url: () => 'http://localhost.test/navigation',
		method: 'POST',
		updateInitials: false,
		reset: true,
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
