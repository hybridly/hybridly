import type { MaybeRefOrGetter, Ref } from 'vue'
import { computed, onMounted, onUnmounted, ref, toValue, watch } from 'vue'

type InfiniteScrollCallback = (() => Promise<unknown> | unknown) | undefined

export interface UseInfiniteScrollOptions {
	nextSentinel: Ref<HTMLElement | undefined>
	previousSentinel: Ref<HTMLElement | undefined>
	root?: MaybeRefOrGetter<Element | null>
	buffer?: MaybeRefOrGetter<number>
	manual?: MaybeRefOrGetter<boolean>
	hasNext?: MaybeRefOrGetter<boolean>
	hasPrevious?: MaybeRefOrGetter<boolean>
	onLoadNext?: InfiniteScrollCallback
	onLoadPrevious?: InfiniteScrollCallback
}

export interface UseInfiniteScrollReturn {
	hasNext: Ref<boolean>
	hasPrevious: Ref<boolean>
	isLoading: Ref<boolean>
	isLoadingNext: Ref<boolean>
	isLoadingPrevious: Ref<boolean>
	loadNext: () => Promise<void>
	loadPrevious: () => Promise<void>
}

export function useInfiniteScroll(options: UseInfiniteScrollOptions): UseInfiniteScrollReturn {
	const isLoadingNext = ref(false)
	const isLoadingPrevious = ref(false)
	const hasNext = computed(() => Boolean(toValue(options.hasNext)))
	const hasPrevious = computed(() => Boolean(toValue(options.hasPrevious)))
	const isLoading = computed(() => isLoadingNext.value || isLoadingPrevious.value)

	let nextObserver: IntersectionObserver | undefined
	let previousObserver: IntersectionObserver | undefined
	let hasSeenNextIntersection = false
	let hasSeenPreviousIntersection = false

	function disconnectObservers() {
		nextObserver?.disconnect()
		previousObserver?.disconnect()
		nextObserver = undefined
		previousObserver = undefined
		hasSeenNextIntersection = false
		hasSeenPreviousIntersection = false
	}

	function shouldExecuteIntersection(direction: 'next' | 'previous', entry: IntersectionObserverEntry | undefined): boolean {
		if (!entry) {
			return false
		}

		const hasSeenIntersection = direction === 'next'
			? hasSeenNextIntersection
			: hasSeenPreviousIntersection

		if (direction === 'next') {
			hasSeenNextIntersection = true
		} else {
			hasSeenPreviousIntersection = true
		}

		return hasSeenIntersection && entry.isIntersecting
	}

	async function execute(direction: 'next' | 'previous') {
		const callback = direction === 'next'
			? options.onLoadNext
			: options.onLoadPrevious
		const loading = direction === 'next'
			? isLoadingNext
			: isLoadingPrevious
		const available = direction === 'next'
			? hasNext.value
			: hasPrevious.value

		if (!callback || !available || loading.value) {
			return
		}

		loading.value = true

		try {
			await callback()
		} finally {
			loading.value = false
		}
	}

	function registerObservers() {
		disconnectObservers()

		if (typeof IntersectionObserver === 'undefined' || toValue(options.manual)) {
			return
		}

		const root = toValue(options.root) ?? null
		const buffer = toValue(options.buffer) ?? 0
		const rootMargin = `${buffer}px 0px ${buffer}px 0px`

		nextObserver = new IntersectionObserver(
			([entry]) => {
				if (shouldExecuteIntersection('next', entry)) {
					void execute('next')
				}
			},
			{ root, rootMargin },
		)

		previousObserver = new IntersectionObserver(
			([entry]) => {
				if (shouldExecuteIntersection('previous', entry)) {
					void execute('previous')
				}
			},
			{ root, rootMargin },
		)

		if (options.nextSentinel.value) {
			nextObserver.observe(options.nextSentinel.value)
		}

		if (options.previousSentinel.value) {
			previousObserver.observe(options.previousSentinel.value)
		}
	}

	onMounted(() => {
		registerObservers()
	})

	onUnmounted(() => {
		disconnectObservers()
	})

	watch(
		() => [
			options.nextSentinel.value,
			options.previousSentinel.value,
			toValue(options.root),
			toValue(options.buffer),
			toValue(options.manual),
			hasNext.value,
			hasPrevious.value,
		],
		() => registerObservers(),
		{ flush: 'post' },
	)

	return {
		hasNext,
		hasPrevious,
		isLoading,
		isLoadingNext,
		isLoadingPrevious,
		loadNext: () => execute('next'),
		loadPrevious: () => execute('previous'),
	}
}
