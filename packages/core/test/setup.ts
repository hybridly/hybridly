import { beforeAll, afterAll, afterEach } from 'vitest'
import { server } from './server'

beforeAll(() => {
	server.listen({ onUnhandledRequest: 'error' })
})

afterAll(() => server.close())
afterEach(() => server.resetHandlers())
