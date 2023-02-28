import type { ResolvedHybridlyConfig } from '@hybridly/config'
import laravel from 'laravel-vite-plugin'
import type { ViteOptions } from '../types'

type LaravelOptions = Exclude<Parameters<typeof laravel>[0], string | string[]>

function getLaravelOptions(options: ViteOptions, config: ResolvedHybridlyConfig): LaravelOptions {
	return {
		input: `${config.root}/application/main.ts`,
		...options.laravel ?? {},
	}
}

export { LaravelOptions, getLaravelOptions, laravel }
