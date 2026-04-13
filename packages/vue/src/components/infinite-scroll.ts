import { constants, type HybridPayload, type HybridRequestOptions, makeUrl, type ProtocolPaginator, router } from '@hybridly/core'
import { wrap } from '@hybridly/utils'
import { get } from 'es-toolkit/compat'
import type { PropType, SlotsType } from 'vue'
import { computed, defineComponent, h, nextTick, onUnmounted, ref, toRaw, watch } from 'vue'
import { useInfiniteScroll } from '../composables/infinite-scroll'
import { state } from '../stores/state'

export interface InfiniteScrollPage {
	key: string
	property: any
	paginator: ProtocolPaginator
}

interface InfiniteScrollSharedSlotProps {
	pages: InfiniteScrollPage[]
	hasNext: boolean
	hasPrevious: boolean
	loading: boolean
	loadingNext: boolean
	loadingPrevious: boolean
	loadNext: () => Promise<void>
	loadPrevious: () => Promise<void>
	manual: boolean
	preserveUrl: boolean
	reverse: boolean
	visiblePage: InfiniteScrollPage | undefined
}

export interface InfiniteScrollDefaultSlotProps extends InfiniteScrollSharedSlotProps {
	page: InfiniteScrollPage
	index: number
	first: boolean
	last: boolean
}

export interface InfiniteScrollDirectionSlotProps extends InfiniteScrollSharedSlotProps {
	direction: 'next' | 'previous'
	available: boolean
	disabled: boolean
	load: () => Promise<void>
}

export interface InfiniteScrollLoadingSlotProps extends InfiniteScrollSharedSlotProps {
	direction: 'next' | 'previous'
}

type InfiniteScrollRequestOptions = Omit<HybridRequestOptions, 'url' | 'data' | 'method' | 'preserveUrl'>

interface ScrollCompensation {
	scrollHeight: number
	scrollTop: number
	root: Element
}

interface InfiniteScrollHistoryState {
	pages: InfiniteScrollPage[]
	visiblePageKey: string | null
}

export const InfiniteScroll = defineComponent({
	name: 'InfiniteScroll',
	props: {
		as: {
			type: String,
			default: 'div',
		},
		data: {
			type: String,
			required: true,
		},
		buffer: {
			type: Number,
			default: 0,
		},
		root: {
			type: [String, Object] as PropType<string | Element | null>,
			default: null,
		},
		manual: {
			type: Boolean,
			default: false,
		},
		reverse: {
			type: Boolean,
			default: false,
		},
		preserveUrl: {
			type: Boolean,
			default: false,
		},
		options: {
			type: Object as PropType<InfiniteScrollRequestOptions>,
			default: () => ({}),
		},
	},
	slots: Object as SlotsType<{
		default: InfiniteScrollDefaultSlotProps
		previous: InfiniteScrollDirectionSlotProps
		next: InfiniteScrollDirectionSlotProps
		loading: InfiniteScrollLoadingSlotProps
	}>,
	setup(props, { slots }) {
		const property = computed(() => get(state.properties.value as unknown as Record<string, unknown>, props.data))
		const paginator = computed(() => state.context.value?.view.paginators?.[props.data])
		const historyKey = computed(() => `infinite-scroll:${state.context.value?.view.component ?? 'unknown'}:${props.data}`)
		const visiblePageKey = ref<string | null>(null)
		const pages = ref<InfiniteScrollPage[]>([])
		const previousSentinel = ref<HTMLElement>()
		const nextSentinel = ref<HTMLElement>()
		const pageElements = new Map<string, Element>()
		const visibilityRatios = new Map<string, number>()
		let pageObserver: IntersectionObserver | undefined
		let isRequestingPage = false

		function isScrollableElement(element: Element): element is HTMLElement {
			if (!(element instanceof HTMLElement)) {
				return false
			}

			const { overflow, overflowY, overflowX } = window.getComputedStyle(element)
			return ['auto', 'scroll', 'overlay'].some((value) => overflow.includes(value) || overflowY.includes(value) || overflowX.includes(value))
		}

		function findScrollParent(element: Element | null | undefined): Element | null {
			let parent: Element | null = element?.parentElement ?? null

			while (parent) {
				if (isScrollableElement(parent)) {
					return parent
				}

				parent = parent.parentElement
			}

			return document.scrollingElement
		}

		function resolveExplicitRoot(): Element | null {
			if (typeof props.root === 'string') {
				return document.querySelector(props.root)
			}

			return props.root
		}

		function resolveCompensationRoot(): Element | null {
			const explicitRoot = resolveExplicitRoot()

			if (explicitRoot) {
				return explicitRoot
			}

			return findScrollParent(
				previousSentinel.value
					?? nextSentinel.value
					?? pageElements.get(pages.value[0]?.key ?? ''),
			)
		}

		function resolveObserverRoot(): Element | null {
			const root = resolveCompensationRoot()

			return root === document.scrollingElement ? null : root
		}

		const visiblePage = computed(() => pages.value.find((page) => page.key === visiblePageKey.value))
		const firstPage = computed(() => pages.value[0])
		const lastPage = computed(() => pages.value.at(-1))
		const currentPage = computed(() => {
			if (!paginator.value) {
				return undefined
			}

			return createPage(property.value, paginator.value)
		})

		const previousQueryValue = computed(() => {
			return props.reverse
				? firstPage.value?.paginator.next
				: firstPage.value?.paginator.previous
		})
		const nextQueryValue = computed(() => {
			return props.reverse
				? lastPage.value?.paginator.previous
				: lastPage.value?.paginator.next
		})

		const {
			isLoading,
			isLoadingNext,
			isLoadingPrevious,
			hasNext,
			hasPrevious,
			loadNext,
			loadPrevious,
		} = useInfiniteScroll({
			nextSentinel,
			previousSentinel,
			root: resolveObserverRoot,
			buffer: () => props.buffer,
			manual: () => props.manual,
			hasNext: () => !!nextQueryValue.value,
			hasPrevious: () => !!previousQueryValue.value,
			onLoadNext: async () => await requestPage('next'),
			onLoadPrevious: async () => await requestPage('previous'),
		})

		watch(currentPage, (page) => {
			if (!page || isRequestingPage) {
				return
			}

			if (pages.value.length === 0) {
				restoreInitialPages(page)
				return
			}

			const existingIndex = pages.value.findIndex((entry) => entry.key === page.key)

			if (existingIndex === -1) {
				pages.value = [page]
				visiblePageKey.value = page.key
				return
			}

			pages.value = pages.value.map((entry) => entry.key === page.key ? page : entry)

			if (visiblePageKey.value === null) {
				visiblePageKey.value = page.key
			}
		}, { immediate: true })

		watch(resolveObserverRoot, () => {
			pageObserver?.disconnect()

			const root = resolveObserverRoot()
			pageObserver = new IntersectionObserver(
				(entries) => {
					for (const entry of entries) {
						const key = (entry.target as HTMLElement).dataset.hybridlyInfiniteScrollPage

						if (!key) {
							continue
						}

						visibilityRatios.set(key, entry.intersectionRatio)
					}

					updateVisiblePage()
				},
				{
					root,
					threshold: [0, 0.25, 0.5, 0.75, 1],
				},
			)

			pageElements.forEach((element) => pageObserver?.observe(element))
		}, { flush: 'post', immediate: true })

		onUnmounted(() => {
			pageObserver?.disconnect()
		})

		watch([pages, visiblePageKey, () => state.context.value?.url], () => {
			if (pages.value.length === 0 && visiblePageKey.value === null) {
				return
			}

			router.history.remember(
				historyKey.value,
				{
					pages: pages.value,
					visiblePageKey: visiblePageKey.value,
				} satisfies InfiniteScrollHistoryState,
			)
		}, { immediate: true })

		watch(visiblePage, async (page) => {
			if (!page || props.preserveUrl) {
				return
			}

			const url = makePaginatorUrl(page.paginator.queryKey, page.paginator.current)
			if (url !== state.context.value?.url) {
				await router.local(url, {
					preserveState: true,
					preserveScroll: true,
					replace: true,
				})
			}
		})

		function createPage(pageProperty: unknown, pagePaginator: ProtocolPaginator): InfiniteScrollPage {
			return {
				key: `${pagePaginator.queryKey}:${pagePaginator.current === null ? '__initial__' : String(pagePaginator.current)}`,
				property: structuredClone(toRaw(pageProperty)),
				paginator: structuredClone(toRaw(pagePaginator)),
			}
		}

		function restoreInitialPages(page: InfiniteScrollPage) {
			const remembered = router.history.get<InfiniteScrollHistoryState | undefined>(historyKey.value)
			const restoredPages = remembered?.pages?.some((entry) => entry.key === page.key)
				? remembered.pages.map((entry) => entry.key === page.key ? page : entry)
				: [page]

			pages.value = restoredPages
			visiblePageKey.value = restoredPages.some((entry) => entry.key === remembered?.visiblePageKey)
				? remembered!.visiblePageKey
				: page.key
		}

		function setVisiblePage(key: string | null) {
			if (visiblePageKey.value === key) {
				return
			}

			visiblePageKey.value = key
		}

		function makePaginatorUrl(queryKey: string, value: string | number | null | undefined): string {
			const url = makeUrl(state.context.value?.url ?? window.location.href)

			if (value === null || value === undefined) {
				url.searchParams.delete(queryKey)
			} else {
				url.searchParams.set(queryKey, String(value))
			}

			return url.toString()
		}

		function capturePrependCompensation(): ScrollCompensation | undefined {
			const root = resolveCompensationRoot()

			if (!root) {
				return
			}

			return {
				scrollHeight: root.scrollHeight,
				scrollTop: root.scrollTop,
				root,
			}
		}

		function restorePrependCompensation(compensation: ScrollCompensation | undefined) {
			if (!compensation) {
				return
			}

			const scrollHeightDelta = compensation.root.scrollHeight - compensation.scrollHeight

			if (scrollHeightDelta === 0) {
				return
			}

			compensation.root.scrollTo({
				top: compensation.scrollTop + scrollHeightDelta,
				left: compensation.root.scrollLeft,
			})
		}

		function updateVisiblePage() {
			const nextVisiblePage = pages.value.reduce<{ key: string; ratio: number } | undefined>((best, page) => {
				const ratio = visibilityRatios.get(page.key) ?? 0

				if (!best || ratio > best.ratio) {
					return { key: page.key, ratio }
				}

				return best
			}, undefined)

			if (!nextVisiblePage || nextVisiblePage.ratio <= 0) {
				return
			}

			setVisiblePage(nextVisiblePage.key)
		}

		function setPageElement(key: string, element: Element | null) {
			const current = pageElements.get(key)

			if (current && current !== element) {
				pageObserver?.unobserve(current)
			}

			if (!element) {
				if (current) {
					pageObserver?.unobserve(current)
				}

				pageElements.delete(key)
				visibilityRatios.delete(key)
				return
			}

			pageElements.set(key, element)
			pageObserver?.observe(element)
		}

		function mergeResolvedPage(page: InfiniteScrollPage, direction: 'next' | 'previous') {
			const existingIndex = pages.value.findIndex((entry) => entry.key === page.key)

			if (existingIndex >= 0) {
				pages.value = pages.value.map((entry) => entry.key === page.key ? page : entry)
				return
			}

			if (direction === 'previous') {
				pages.value = [page, ...pages.value]
				return
			}

			pages.value = [...pages.value, page]
		}

		async function requestPage(direction: 'next' | 'previous') {
			const edgePage = direction === 'previous' ? firstPage.value : lastPage.value
			const queryValue = direction === 'previous' ? previousQueryValue.value : nextQueryValue.value

			if (!edgePage || queryValue === null || queryValue === undefined) {
				return
			}

			const userHooks = props.options.hooks ?? {}
			const url = makePaginatorUrl(edgePage.paginator.queryKey, queryValue)

			if (direction === 'previous') {
				// Wait for the loading slot above the list to render so prepend compensation
				// measures the same layout state that will exist until the request resolves.
				await nextTick()
			}

			const prependCompensation = direction === 'previous'
				? capturePrependCompensation()
				: undefined
			let resolvedPage: InfiniteScrollPage | undefined

			isRequestingPage = true

			try {
				await router.get(url, {
					...props.options,
					only: Array.from(new Set([...wrap(props.options.only) as string[], props.data])),
					mode: props.options.mode ?? 'async',
					preserveState: props.options.preserveState ?? true,
					preserveScroll: props.options.preserveScroll ?? true,
					replace: props.options.replace ?? true,
					preserveUrl: true,
					headers: {
						...props.options.headers,
						[constants.MERGE_INTENT_HEADER]: JSON.stringify({
							[props.data]: direction === 'previous' ? 'prepend' : 'append',
						}),
					},
					hooks: {
						...userHooks,
						data: async (request, response, context) => {
							const payload = response.data as HybridPayload
							const nextProperty = get(payload.view.properties, props.data)
							const nextPaginator = payload.view.paginators?.[props.data]

							if (nextPaginator !== undefined) {
								resolvedPage = createPage(nextProperty, nextPaginator)
							}

							return await userHooks.data?.(request, response, context)
						},
						success: async (payload, request, response, context) => {
							if (resolvedPage !== undefined) {
								mergeResolvedPage(resolvedPage, direction)
								await nextTick()
								restorePrependCompensation(prependCompensation)
							}

							await userHooks.success?.(payload, request, response, context)
						},
					},
				})
			} finally {
				isRequestingPage = false
			}
		}

		return () => {
			const commonProperties = {
				pages: pages.value,
				hasNext: hasNext.value,
				hasPrevious: hasPrevious.value,
				loading: isLoading.value,
				loadingNext: isLoadingNext.value,
				loadingPrevious: isLoadingPrevious.value,
				loadNext,
				loadPrevious,
				manual: props.manual,
				preserveUrl: props.preserveUrl,
				reverse: props.reverse,
				visiblePage: visiblePage.value,
			}

			const previousSlotProperties: InfiniteScrollDirectionSlotProps = {
				...commonProperties,
				direction: 'previous',
				available: hasPrevious.value,
				disabled: !hasPrevious.value || isLoadingPrevious.value,
				load: loadPrevious,
			}
			const nextSlotProperties: InfiniteScrollDirectionSlotProps = {
				...commonProperties,
				direction: 'next',
				available: hasNext.value,
				disabled: !hasNext.value || isLoadingNext.value,
				load: loadNext,
			}

			return h(props.as, [
				h('div', {
					ref: previousSentinel,
					style: 'min-height: 1px;',
					'data-slot': 'previous-sentinel',
				}, [
					slots.previous?.(previousSlotProperties),
					isLoadingPrevious.value
						? slots.loading?.({ ...commonProperties, direction: 'previous' })
						: null,
				]),
				...pages.value.map((page, index) =>
					h(
						'div',
						{
							key: page.key,
							ref: (element) => setPageElement(page.key, element as Element | null),
							'data-hybridly-infinite-scroll-page': page.key,
						},
						slots.default?.({
							...commonProperties,
							page,
							index,
							first: index === 0,
							last: index === pages.value.length - 1,
						} as InfiniteScrollDefaultSlotProps),
					)
				),
				h('div', {
					ref: nextSentinel,
					style: 'min-height: 1px;',
					'data-slot': 'next-sentinel',
				}, [
					isLoadingNext.value
						? slots.loading?.({ ...commonProperties, direction: 'next' })
						: null,
					slots.next?.(nextSlotProperties),
				]),
			])
		}
	},
})
