import laravel from 'laravel-vite-plugin'
import type { DynamicConfiguration } from '@hybridly/core'
import type { ViteOptions } from '../types'

type LaravelOptions = Exclude<Parameters<typeof laravel>[0], string | string[]>

function getLaravelOptions(options: ViteOptions, config: DynamicConfiguration): LaravelOptions {
	return {
		input: `${config.architecture.root}/application/main.ts`,
		ssr: config.ssr ? `${config.architecture.root}/application/main.ts` : undefined,
		...options.laravel ?? {},
	}
}

export { LaravelOptions, getLaravelOptions, laravel }
