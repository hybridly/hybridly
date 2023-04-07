import { beforeAll, afterAll, afterEach } from 'vitest'
import { server } from './server'

afterAll(() => server.close())
afterEach(() => server.resetHandlers())

beforeAll(() => {
	server.listen({ onUnhandledRequest: 'error' })
})
