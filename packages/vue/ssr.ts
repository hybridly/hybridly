import createFastifyServer from 'fastify'
import middie from '@fastify/middie'
import { createServer as createViteServer } from 'vite'

async function createServer() {
	const fastify = await createFastifyServer({
		logger: true,
	})

	// Create Vite server in middleware mode and configure the app type as
	// 'custom', disabling Vite's own HTML serving logic so parent server
	// can take control
	const vite = await createViteServer({
		server: { middlewareMode: true },
		appType: 'custom',
	})

	// Use vite's connect instance as middleware. If you use your own
	// express router (express.Router()), you should use router.use
	await fastify.register(middie)
	fastify.use(vite.middlewares)

	fastify.addContentTypeParser('application/json', { parseAs: 'string' }, (req, body, done) => {
		try {
			done(null, JSON.parse(body.toString()))
		} catch (err) {
			err.statusCode = 400
			done(err)
		}
	})

	fastify.post('/shutdown', async() => {
		fastify.log.info('Closing server...')
		await fastify.close()
		fastify.log.info('Closed server')
	})

	fastify.post('/render', async(req, res) => {
		const mod = await vite.ssrLoadModule('./resources/application/main.ts')
		const render = await Promise.resolve(mod.render ?? mod.default)
		console.log({ body: req.body, render })
		const html = await render(req.body)
		console.log({ html, body: req.body })

		res.status(200).send({ 'Content-Type': 'text/html' }).send(html)
	})

	try {
		await fastify.listen({ port: 13714 })
	} catch (err) {
		console.log(err)
		process.exit(1)
	}
}

createServer()
