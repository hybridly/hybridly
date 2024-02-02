import { setupServer } from 'msw/node'
import { http } from 'msw'

const server = setupServer()

server.listen({ onUnhandledRequest: 'error' })

export { server, http }
