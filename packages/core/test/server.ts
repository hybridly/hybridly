import { setupServer } from 'msw/node'
import { http } from 'msw'

const server = setupServer()

export { server, http }
