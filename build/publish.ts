import { $, Glob } from 'bun'
import { join } from 'node:path'

interface PackageJson {
	name?: string
	version?: string
	private?: boolean
	workspaces?: {
		packages?: string[]
	}
}

const dryRun = process.argv.includes('--dry-run')

if (dryRun) {
	console.log('🔍 Dry run mode enabled - no packages will be published\n')
}

const rootPackageJson = await Bun.file('package.json').json() as PackageJson
const workspaces = rootPackageJson.workspaces?.packages || []

if (workspaces.length === 0) {
	console.error('No workspace packages found in package.json')
	process.exit(1)
}

const packages: string[] = []
for (const pattern of workspaces) {
	const glob = new Glob(pattern)

	for await (const dir of glob.scan({ cwd: process.cwd(), onlyFiles: false })) {
		packages.push(dir)
	}
}

console.log(`Found ${packages.length} packages to publish\n`)

for (const directory of packages) {
	try {
		const packageJson = await Bun.file(`${directory}/package.json`).json() as PackageJson

		if (packageJson.private || !packageJson.name || !packageJson.version) {
			console.log(`⏭️  Skipping private or misconfigured package: ${packageJson.name}`)
			continue
		}

		console.log(`→ Packing...`)
		await $`bun pm pack --filename ${directory}/package.tgz`.cwd(directory)

		const isPrerelease = /-(alpha|beta|rc|next|canary|dev)\.\d+/i.test(packageJson.version)
		const tag = isPrerelease ? 'next' : 'latest'

		console.log(`  → Publishing ${packageJson.name}@${packageJson.version}...`)
    await $`npm publish package.tgz --provenance --access public --tag ${tag} ${dryRun ? '--dry-run' : ''}`.cwd(directory)

		console.log(`  ✅ Successfully published ${packageJson.name}\n`)
	} catch (error) {
		console.error(`  ❌ Failed to publish package at ${directory}:`, error)
	} finally {
    const tarball = Bun.file(`${directory}/package.tgz`)
    if (await tarball.exists()) {
      await tarball.delete()
    }
  }
}

console.log('✨ All packages published successfully!')
