import { beforeAll, afterAll, afterEach } from 'vitest'
import { FormData } from './mocks/form-data'
import { server } from './server'

afterAll(() => server.close())
afterEach(() => server.resetHandlers())

beforeAll(() => {
	server.listen({ onUnhandledRequest: 'error' })
	// @ts-expect-error
	globalThis.FormData = FormData
})
