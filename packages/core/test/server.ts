import { setupServer } from 'msw/node'
import { rest } from 'msw'

const server = setupServer()

export { server, rest }
