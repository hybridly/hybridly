import { basename } from 'node:path'
import type { ResolvedHybridlyConfig } from '@hybridly/config'
import { expect, test } from 'vitest'
import { resolveComponentUsingPaths } from '../src/integrations/vue-components'

const paths = {
	'/Users/enzoinnocenzi/Code/http/dashboard/resources/domains/domain/components/foo.vue': 'DomainFoo',
	'/Users/enzoinnocenzi/Code/http/dashboard/resources/domains/domain/components/foo-kebab.vue': 'DomainFooKebab',
	'/Users/enzoinnocenzi/Code/http/dashboard/resources/domains/domain/components/foo/bar.vue': 'DomainFooBar',
	'/Users/enzoinnocenzi/Code/http/dashboard/resources/domains/domain/components/foo/bar-baz.vue': 'DomainFooBarBaz',
	'/Users/enzoinnocenzi/Code/http/dashboard/resources/domains/domain/components/Bar.vue': 'DomainBar',
	'/Users/enzoinnocenzi/Code/http/dashboard/resources/domains/domain/components/BarPascal.vue': 'DomainBarPascal',
	'/Users/enzoinnocenzi/Code/http/dashboard/resources/domains/domain/components/Baz/Pascal.vue': 'DomainBazPascal',
	'/Users/enzoinnocenzi/Code/http/dashboard/resources/domains/kebab-domain/components/foo.vue': 'KebabDomainFoo',
	'/Users/enzoinnocenzi/Code/http/dashboard/resources/domains/kebab-domain/components/foo-kebab.vue': 'KebabDomainFooKebab',
	'/Users/enzoinnocenzi/Code/http/dashboard/resources/domains/kebab-domain/components/Bar.vue': 'KebabDomainBar',
	'/Users/enzoinnocenzi/Code/http/dashboard/resources/domains/kebab-domain/components/BarPascal.vue': 'KebabDomainBarPascal',
	'/Users/enzoinnocenzi/Code/http/dashboard/resources/domains/kebab-domain/components/Uwu/BarPascal.vue': 'KebabDomainUwuBarPascal',
}

const config: ResolvedHybridlyConfig = {
	domains: 'domains',
	root: 'resources',
	layouts: 'layouts',
	pages: 'pages',
	pagesGlob: '',
}

const resolve = (...part: string[]) => part.join('/')

test('stray components are resolved to their path on disk', () => {
	for (const [path, component] of Object.entries(paths)) {
		const result = resolveComponentUsingPaths(
			config,
			Object.keys(paths),
			component,
			resolve,
		)

		expect(result).toBe(path)
	}
})

test('components without domains are not resolved to similar paths on disk', () => {
	for (const component of Object.keys(paths).map((path) => basename(path, '.vue'))) {
		const result = resolveComponentUsingPaths(
			config,
			Object.keys(paths),
			component!,
			resolve,
		)

		expect(result).toBeUndefined()
	}
})
