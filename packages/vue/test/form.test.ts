import { beforeEach, it } from 'vitest'
import { useForm } from '@hybridly/vue'
import { nextTick } from 'vue'
import { fakeRouterContext, mockSuccessfulUrl, mockInvalidUrl } from '../../core/test/utils'
import { server } from '../../core/test/server'

beforeEach(async() => {
	await fakeRouterContext()
})

it('it resets dirty state after successful form submission', async({ expect }) => {
	// Given
	server.resetHandlers(mockSuccessfulUrl())

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
	server.resetHandlers(mockInvalidUrl())

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
