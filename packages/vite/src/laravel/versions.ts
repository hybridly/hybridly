import path from 'node:path'
import fs from 'node:fs'
import { dirname } from './utils'

export function getPluginVersion(): string {
	try {
		return JSON.parse(fs.readFileSync(path.join(dirname(), '../../package.json')).toString())?.version
	} catch {
		return ''
	}
}

export function getLaravelVersion(): string {
	try {
		const composer = JSON.parse(fs.readFileSync('composer.lock').toString())

		return composer.packages?.find((composerPackage: { name: string }) => composerPackage.name === 'laravel/framework')?.version ?? ''
	} catch {
		return ''
	}
}
