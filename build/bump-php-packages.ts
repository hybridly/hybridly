import { $ } from 'bun'

async function main() {
	const { version } = await Bun.file('package.json').json()
	await $`composer monorepo:merge`
	await $`composer monorepo:alias`
	await $`composer monorepo:bump ${version}`
	await $`composer monorepo:validate`
}

main()
