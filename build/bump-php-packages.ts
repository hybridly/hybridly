import fs from 'node:fs'
import * as ezSpawn from '@jsdevtools/ez-spawn'

async function main() {
	const { version } = JSON.parse(fs.readFileSync('package.json', { encoding: 'utf-8' }))
	await ezSpawn.async('composer', ['monorepo:release', '--', version], { stdio: 'inherit' })
}

main()
