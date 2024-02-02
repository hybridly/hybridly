import { afterAll, vi } from 'vitest'

afterAll(() => {
	vi.unstubAllGlobals()
	vi.unstubAllEnvs()
})
