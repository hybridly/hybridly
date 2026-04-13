import { constants, router } from '@hybridly/core'
import { InfiniteScroll } from '@hybridly/vue'
import { mount } from '@vue/test-utils'
import { HttpResponse } from 'msw'
import { beforeEach, test, vi } from 'vitest'
import { defineComponent, nextTick } from 'vue'
import { http, server } from '../../core/test/server'
import { fakePayload, fakeRouter } from '../../core/test/utils'
import { state } from '../../vue/src/stores/state'

class MockIntersectionObserver {
	public static instances: MockIntersectionObserver[] = []

	public readonly observed = new Set<Element>()

	public constructor(
		public readonly callback: IntersectionObserverCallback,
		public readonly options?: IntersectionObserverInit,
	) {
		MockIntersectionObserver.instances.push(this)
	}

	public observe(element: Element) {
		this.observed.add(element)
	}

	public unobserve(element: Element) {
		this.observed.delete(element)
	}

	public disconnect() {
		this.observed.clear()
	}
}

beforeEach(async () => {
	server.resetHandlers()
	MockIntersectionObserver.instances = []
	vi.stubGlobal('IntersectionObserver', MockIntersectionObserver as unknown as typeof IntersectionObserver)

	const hybridly = await fakeRouter({
		payload: {
			url: 'https://bluebird.test/feed?page=2',
			view: {
				component: 'feed.view',
				properties: {
					feed: {
						data: [{ id: 3, label: 'Page 2' }],
					},
				},
				deferred: {},
				mergeable: [['feed', false, 'id', ['data']]],
				paginators: {
					feed: {
						type: 'length-aware',
						queryKey: 'page',
						current: 2,
						previous: 1,
						next: 3,
					},
				},
			},
		},
		adapter: {
			executeOnMounted: (callback) => callback(),
			onContextUpdate: (context) => state.setContext(context),
			onViewSwap: async (options) => {
				if (options.properties) {
					state.setProperties(options.properties)
				}
			},
		},
	})

	state.setContext(hybridly)
	state.setProperties(hybridly.view.properties as any)
	router.history.remember('infinite-scroll:feed.view:feed', undefined)
})

test('it prepends and appends page chunks while sending merge intent headers', async ({ expect }) => {
	const headers: string[] = []
	const urls: string[] = []

	server.use(
		http.get('https://bluebird.test/feed', ({ request }) => {
			const url = new URL(request.url)
			const page = Number(url.searchParams.get('page'))

			headers.push(request.headers.get('x-hybrid-merge-intent') ?? '')
			urls.push(url.toString())

			return HttpResponse.json(
				fakePayload({
					url: url.toString(),
					view: {
						component: 'feed.view',
						properties: {
							feed: {
								data: [{ id: page * 2 - 1, label: `Page ${page}` }],
							},
						},
						deferred: {},
						mergeable: [['feed', page === 1, 'id', ['data']]],
						paginators: {
							feed: {
								type: 'length-aware',
								queryKey: 'page',
								current: page,
								previous: page > 1 ? page - 1 : null,
								next: page < 3 ? page + 1 : null,
							},
						},
					},
				}),
				{
					headers: {
						[constants.HYBRIDLY_HEADER]: 'true',
					},
				},
			)
		}),
	)

	const TestComponent = defineComponent({
		components: { InfiniteScroll },
		template: `
			<InfiniteScroll data="feed" manual>
				<template #previous="{ available, load }">
					<button v-if="available" data-testid="previous" @click="load">Previous</button>
				</template>
				<template #default="{ page }">
					<div class="page" :data-page="page.paginator.current ?? 1">{{ page.property.data[0].label }}</div>
				</template>
				<template #next="{ available, load }">
					<button v-if="available" data-testid="next" @click="load">Next</button>
				</template>
			</InfiniteScroll>
		`,
	})

	const wrapper = mount(TestComponent)

	expect(wrapper.findAll('.page').map((page) => page.text())).toEqual(['Page 2'])

	await wrapper.get('[data-testid="previous"]').trigger('click')
	await vi.waitFor(() => {
		expect(wrapper.findAll('.page').map((page) => page.text())).toEqual(['Page 1', 'Page 2'])
	})

	await wrapper.get('[data-testid="next"]').trigger('click')
	await vi.waitFor(() => {
		expect(urls).toEqual([
			'https://bluebird.test/feed?page=1',
			'https://bluebird.test/feed?page=3',
		])
	})
	await vi.waitFor(() => {
		expect(wrapper.findAll('.page').map((page) => page.text())).toEqual(['Page 1', 'Page 2', 'Page 3'])
	})

	expect(headers).toEqual([
		JSON.stringify({ feed: 'prepend' }),
		JSON.stringify({ feed: 'append' }),
	])
})

test('it syncs the visible page URL through router.local by default and stops when preserveUrl is enabled', async ({ expect }) => {
	server.use(
		http.get('https://bluebird.test/feed', ({ request }) => {
			const url = new URL(request.url)
			const page = Number(url.searchParams.get('page'))

			return HttpResponse.json(
				fakePayload({
					url: url.toString(),
					view: {
						component: 'feed.view',
						properties: {
							feed: {
								data: [{ id: page * 2 - 1, label: `Page ${page}` }],
							},
						},
						deferred: {},
						mergeable: [['feed', false, 'id', ['data']]],
						paginators: {
							feed: {
								type: 'length-aware',
								queryKey: 'page',
								current: page,
								previous: page > 1 ? page - 1 : null,
								next: page < 3 ? page + 1 : null,
							},
						},
					},
				}),
				{
					headers: {
						[constants.HYBRIDLY_HEADER]: 'true',
					},
				},
			)
		}),
	)

	const localSpy = vi.spyOn(router, 'local')

	const TestComponent = defineComponent({
		components: { InfiniteScroll },
		data: () => ({ preserveUrl: undefined as boolean | undefined }),
		template: `
			<InfiniteScroll data="feed" manual :preserve-url="preserveUrl">
				<template #previous="{ available, load }">
					<button v-if="available" data-testid="previous" @click="load">Previous</button>
				</template>
				<template #default="{ page }">
					<div class="page">{{ page.property.data[0].label }}</div>
				</template>
				<template #next="{ available, load }">
					<button v-if="available" data-testid="next" @click="load">Next</button>
				</template>
			</InfiniteScroll>
		`,
	})

	const wrapper = mount(TestComponent)

	await wrapper.get('[data-testid="next"]').trigger('click')
	await vi.waitFor(() => {
		expect(wrapper.findAll('.page').map((page) => page.text())).toEqual(['Page 2', 'Page 3'])
	})

	const page = wrapper.get('[data-hybridly-infinite-scroll-page="page:3"]').element
	const observer = MockIntersectionObserver.instances.find((observer) => observer.observed.has(page))

	observer?.callback([
		{ target: page, intersectionRatio: 1, isIntersecting: true } as IntersectionObserverEntry,
	], observer as unknown as IntersectionObserver)

	await vi.waitFor(() => {
		expect(localSpy).toHaveBeenCalledWith('https://bluebird.test/feed?page=3', {
			preserveState: true,
			preserveScroll: true,
			replace: true,
		})
	})

	localSpy.mockClear()
	await wrapper.setData({ preserveUrl: true })

	const secondPage = wrapper.get('[data-hybridly-infinite-scroll-page="page:2"]').element
	observer?.callback([
		{ target: secondPage, intersectionRatio: 1, isIntersecting: true } as IntersectionObserverEntry,
	], observer as unknown as IntersectionObserver)

	await vi.waitFor(() => {
		expect(localSpy).not.toHaveBeenCalled()
	})
})

test('it does not auto-load adjacent pages from the initial sentinel intersections', async ({ expect }) => {
	const urls: string[] = []

	server.use(
		http.get('https://bluebird.test/feed', ({ request }) => {
			const url = new URL(request.url)
			const page = Number(url.searchParams.get('page'))

			urls.push(url.toString())

			return HttpResponse.json(
				fakePayload({
					url: url.toString(),
					view: {
						component: 'feed.view',
						properties: {
							feed: {
								data: [{ id: page * 2 - 1, label: `Page ${page}` }],
							},
						},
						deferred: {},
						mergeable: [['feed', false, 'id', ['data']]],
						paginators: {
							feed: {
								type: 'length-aware',
								queryKey: 'page',
								current: page,
								previous: page > 1 ? page - 1 : null,
								next: page < 3 ? page + 1 : null,
							},
						},
					},
				}),
				{
					headers: {
						[constants.HYBRIDLY_HEADER]: 'true',
					},
				},
			)
		}),
	)

	const TestComponent = defineComponent({
		components: { InfiniteScroll },
		template: `
			<div id="root">
				<InfiniteScroll data="feed" root="#root">
					<template #default="{ page }">
						<div class="page">{{ page.property.data[0].label }}</div>
					</template>
				</InfiniteScroll>
			</div>
		`,
	})

	mount(TestComponent)

	const sentinelObservers = MockIntersectionObserver.instances.filter((observer) => observer.options?.rootMargin !== undefined).slice(-2)

	expect(sentinelObservers).toHaveLength(2)

	for (const observer of sentinelObservers) {
		const target = observer.observed.values().next().value as Element

		observer.callback([
			{ target, intersectionRatio: 1, isIntersecting: true } as IntersectionObserverEntry,
		], observer as unknown as IntersectionObserver)
	}

	await Promise.resolve()
	expect(urls).toEqual([])

	for (const observer of sentinelObservers) {
		const target = observer.observed.values().next().value as Element

		observer.callback([
			{ target, intersectionRatio: 0, isIntersecting: false } as IntersectionObserverEntry,
		], observer as unknown as IntersectionObserver)
		observer.callback([
			{ target, intersectionRatio: 1, isIntersecting: true } as IntersectionObserverEntry,
		], observer as unknown as IntersectionObserver)
	}

	await vi.waitFor(() => {
		expect(urls).toEqual([
			'https://bluebird.test/feed?page=3',
			'https://bluebird.test/feed?page=1',
		])
	})
})

test('it does not adjust the custom scroll root on initial mount', async ({ expect }) => {
	const scrollTo = vi.fn()
	const originalScrollTo = HTMLElement.prototype.scrollTo

	HTMLElement.prototype.scrollTo = scrollTo

	const TestComponent = defineComponent({
		components: { InfiniteScroll },
		template: `
			<div id="root">
				<InfiniteScroll data="feed" root="#root" manual>
					<template #default="{ page }">
						<div class="page">{{ page.property.data[0].label }}</div>
					</template>
				</InfiniteScroll>
			</div>
		`,
	})

	const wrapper = mount(TestComponent, {
		attachTo: document.body,
	})

	expect(scrollTo).not.toHaveBeenCalled()

	wrapper.unmount()
	HTMLElement.prototype.scrollTo = originalScrollTo
})

test('it syncs the current router page into local state', async ({ expect }) => {
	const TestComponent = defineComponent({
		components: { InfiniteScroll },
		template: `
			<InfiniteScroll data="feed" manual>
				<template #default="{ page }">
					<div class="page">{{ page.property.data[0].label }}</div>
				</template>
			</InfiniteScroll>
		`,
	})

	const wrapper = mount(TestComponent)

	expect(wrapper.findAll('.page').map((page) => page.text())).toEqual(['Page 2'])

	state.setProperties({
		feed: {
			data: [{ id: 3, label: 'Updated Page 2' }],
		},
	} as any)

	await nextTick()

	expect(wrapper.findAll('.page').map((page) => page.text())).toEqual(['Updated Page 2'])

	state.setContext({
		...state.context.value!,
		url: 'https://bluebird.test/feed?page=1',
		view: {
			...state.context.value!.view,
			paginators: {
				...state.context.value!.view.paginators,
				feed: {
					...state.context.value!.view.paginators.feed,
					current: 1,
					previous: null,
					next: 2,
				},
			},
		},
	})
	state.setProperties({
		feed: {
			data: [{ id: 1, label: 'Page 1' }],
		},
	} as any)

	await nextTick()

	expect(wrapper.findAll('.page').map((page) => page.text())).toEqual(['Page 1'])
	wrapper.unmount()
})

test('it preserves prepend position inside the auto-detected scroll container', async ({ expect }) => {
	server.use(
		http.get('https://bluebird.test/feed', ({ request }) => {
			const url = new URL(request.url)
			const page = Number(url.searchParams.get('page'))

			return HttpResponse.json(
				fakePayload({
					url: url.toString(),
					view: {
						component: 'feed.view',
						properties: {
							feed: {
								data: [{ id: page * 2 - 1, label: `Page ${page}` }],
							},
						},
						deferred: {},
						mergeable: [['feed', page === 1, 'id', ['data']]],
						paginators: {
							feed: {
								type: 'length-aware',
								queryKey: 'page',
								current: page,
								previous: page > 1 ? page - 1 : null,
								next: page < 3 ? page + 1 : null,
							},
						},
					},
				}),
				{
					headers: {
						[constants.HYBRIDLY_HEADER]: 'true',
					},
				},
			)
		}),
	)

	const TestComponent = defineComponent({
		components: { InfiniteScroll },
		template: `
			<div id="root" style="overflow:auto;height:200px;">
				<InfiniteScroll data="feed" manual>
					<template #previous="{ available, load }">
						<button v-if="available" data-testid="previous" @click="load">Previous</button>
					</template>
					<template #loading>
						<div data-testid="loading">Loading previous page</div>
					</template>
					<template #default="{ page }">
						<div class="page">{{ page.property.data[0].label }}</div>
					</template>
				</InfiniteScroll>
			</div>
		`,
	})

	const wrapper = mount(TestComponent, {
		attachTo: document.body,
	})

	const root = wrapper.get('#root').element as HTMLElement
	const rootScrollTo = vi.fn()

	Object.defineProperty(root, 'scrollTop', { value: 120, writable: true })
	Object.defineProperty(root, 'scrollLeft', { value: 0, writable: true })
	Object.defineProperty(root, 'scrollHeight', {
		configurable: true,
		get: () => {
			const loading = wrapper.find('[data-testid="loading"]').exists()
			const pages = wrapper.findAll('.page').length

			if (pages > 1) {
				return loading ? 480 : 460
			}

			return loading ? 420 : 400
		},
	})
	root.scrollTo = rootScrollTo

	await wrapper.get('[data-testid="previous"]').trigger('click')

	await vi.waitFor(() => {
		expect(rootScrollTo).toHaveBeenCalledWith({
			top: 180,
			left: 0,
		})
	})

	expect(rootScrollTo).toHaveBeenCalledTimes(1)
	wrapper.unmount()
})

test('it loads through the mapped direction in reverse mode', async ({ expect }) => {
	server.use(
		http.get('https://bluebird.test/feed', ({ request }) => {
			const url = new URL(request.url)
			const page = Number(url.searchParams.get('page'))

			return HttpResponse.json(
				fakePayload({
					url: url.toString(),
					view: {
						component: 'feed.view',
						properties: {
							feed: {
								data: [{ id: page * 2 - 1, label: `Page ${page}` }],
							},
						},
						deferred: {},
						mergeable: [['feed', false, 'id', ['data']]],
						paginators: {
							feed: {
								type: 'length-aware',
								queryKey: 'page',
								current: page,
								previous: page > 1 ? page - 1 : null,
								next: page < 3 ? page + 1 : null,
							},
						},
					},
				}),
				{
					headers: {
						[constants.HYBRIDLY_HEADER]: 'true',
					},
				},
			)
		}),
	)

	const TestComponent = defineComponent({
		components: { InfiniteScroll },
		template: `
			<InfiniteScroll data="feed" manual reverse>
				<template #previous="{ available, load }">
					<button v-if="available" data-testid="previous" @click="load">Previous</button>
				</template>
				<template #default="{ page }">
					<div class="page">{{ page.property.data[0].label }}</div>
				</template>
				<template #next="{ available, load }">
					<button v-if="available" data-testid="next" @click="load">Next</button>
				</template>
			</InfiniteScroll>
		`,
	})

	const wrapper = mount(TestComponent)

	expect(wrapper.get('[data-testid="previous"]').text()).toBe('Previous')
	expect(wrapper.get('[data-testid="next"]').text()).toBe('Next')

	await wrapper.get('[data-testid="next"]').trigger('click')

	await vi.waitFor(() => {
		expect(wrapper.findAll('.page').map((page) => page.text())).toEqual(['Page 2', 'Page 1'])
	})
})

test('it restores remembered pages for the current history entry', async ({ expect }) => {
	router.history.remember('infinite-scroll:feed.view:feed', {
		pages: [
			{
				key: 'page:2',
				property: { data: [{ id: 3, label: 'Stale page 2' }] },
				paginator: {
					type: 'length-aware',
					queryKey: 'page',
					current: 2,
					previous: 1,
					next: 3,
				},
			},
			{
				key: 'page:3',
				property: { data: [{ id: 5, label: 'Remembered page 3' }] },
				paginator: {
					type: 'length-aware',
					queryKey: 'page',
					current: 3,
					previous: 2,
					next: null,
				},
			},
		],
		visiblePageKey: 'page:3',
	})

	const TestComponent = defineComponent({
		components: { InfiniteScroll },
		template: `
			<InfiniteScroll data="feed" manual>
				<template #default="{ page }">
					<div class="page">{{ page.property.data[0].label }}</div>
				</template>
			</InfiniteScroll>
		`,
	})

	const wrapper = mount(TestComponent)

	expect(wrapper.findAll('.page').map((page) => page.text())).toEqual([
		'Page 2',
		'Remembered page 3',
	])

	expect(router.history.get('infinite-scroll:feed.view:feed')).toMatchObject({
		visiblePageKey: 'page:3',
	})
})
