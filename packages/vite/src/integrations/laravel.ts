import laravel from 'laravel-vite-plugin'
import type { Configuration, ViteOptions } from '../types'

type LaravelOptions = Exclude<Parameters<typeof laravel>[0], string | string[]>

function getLaravelOptions(options: ViteOptions, config: Configuration): LaravelOptions {
	return {
		input: `${config.architecture.root}/application/main.ts`,
		...options.laravel ?? {},
	}
}

export { LaravelOptions, getLaravelOptions, laravel }
