import { setupServer } from 'msw/node'
import { afterAll, beforeAll } from 'vitest'

export function createServer() {
	const server = setupServer()

	beforeAll(() => {
		server.listen({ onUnhandledRequest: 'error' })
	})

	afterAll(() => {
		server.close()
	})

	return server
}
