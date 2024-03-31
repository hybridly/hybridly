import { beforeEach, it, vi } from 'vitest'
import { useForm } from '@hybridly/vue'
import { nextTick } from 'vue'
import { fakeRouterContext, mockSuccessfulUrl, mockInvalidUrl } from '../../core/test/utils'
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
