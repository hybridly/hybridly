import qs from 'qs'
import type { PropType } from 'vue'
import { defineComponent, h } from 'vue'
import type { HybridRequestOptions, Method } from '@hybridly/core'
import { makeUrl, router } from '@hybridly/core'
import type { RequestData } from '@hybridly/utils'
import { debug, merge } from '@hybridly/utils'

export const RouterLink = defineComponent({
	name: 'RouterLink',
	setup(_, { slots, attrs }) {
		return (props: typeof _) => {
			let data = props.data ?? {}
			const async = props.async
			const preloads = props.preload ?? false
			const preserveScroll = props.preserveScroll
			const preserveState = props.preserveState
			const url = makeUrl(props.href ?? '')
			const method: Method = props.method?.toUpperCase() as Method ?? 'GET'
			const as = typeof props.as === 'object'
				? props.as
				: props.as?.toLowerCase() ?? 'a'

			// When performing a GET request, we want to move all the data into the URL
			// query string.
			if (method === 'GET') {
				debug.adapter('vue', 'Moving data object to URL parameters.')
				url.search = qs.stringify(merge(data as any, qs.parse(url.search, { ignoreQueryPrefix: true })), {
					encodeValuesOnly: true,
					arrayFormat: 'indices',
				})
				data = {}
			}

			if (as === 'a' && method !== 'GET') {
				debug.adapter('vue', `Creating POST/PUT/PATCH/DELETE <a> links is discouraged as it causes "Open Link in New Tab/Window" accessibility issues.\n\nPlease specify a more appropriate element using the "as" attribute. For example:\n\n<RouterLink href="${url}" method="${method}" as="button">...</RouterLink>`)
			}

			function performPreload(type: 'hover' | 'mount') {
				if (!preloads) {
					return
				}

				if (props.external) {
					return
				}

				if (method !== 'GET') {
					return
				}

				if (type !== 'mount' && props.disabled) {
					return
				}

				if (type === 'hover' && preloads === 'mount') {
					return
				}

				if (type === 'mount' && preloads !== 'mount') {
					// eslint-disable-next-line no-useless-return
					return
				}

				// router.preload(url, {
				// 	data,
				// 	preserveScroll,
				// 	preserveState,
				// 	...props.options,
				// })
			}

			performPreload('mount')

			return h(props.as as any, {
				...attrs,
				...as === 'a' ? { href: url } : {},
				...props.disabled ? { disabled: props.disabled } : {},
				onMouseenter: () => performPreload('hover'),
				onClick: (event: KeyboardEvent) => {
					if (props.disabled) {
						return
					}

					// If the target is external, we don't want hybridly to handle the
					// navigation, so we return early to avoid preventing the event.
					if (props.external) {
						return
					}

					if (!shouldIntercept(event)) {
						return
					}

					event.preventDefault()

					router.navigate({
						url,
						data,
						method,
						async,
						preserveState: (method !== 'GET'),
						...props.options,
					})
				},
			}, slots.default ? slots : props.text)
		}
	},
	props: {
		href: {
			type: String,
			required: false,
			default: undefined,
		},
		as: {
			type: [String, Object],
			default: 'a',
		},
		method: {
			type: String as PropType<Method | Lowercase<Method>>,
			default: 'GET',
		},
		data: {
			type: Object as PropType<RequestData>,
			default: () => ({}),
		},
		external: {
			type: Boolean,
			default: false,
		},
		disabled: {
			type: Boolean,
			default: false,
		},
		options: {
			type: Object as PropType<Omit<HybridRequestOptions, 'url' | 'data' | 'method'>>,
			default: () => ({}),
		},
		text: {
			type: String,
			required: false,
			default: undefined,
		},
		preload: {
			type: [Boolean, String] as PropType<boolean | 'hover' | 'mount'>,
			default: false,
		},
		preserveScroll: {
			type: Boolean,
			default: undefined,
		},
		preserveState: {
			type: Boolean,
			default: undefined,
		},
		async: {
			type: Boolean,
			default: undefined,
		},
	},
})

function shouldIntercept(event: KeyboardEvent): boolean {
	const isLink = (event.currentTarget as HTMLElement).tagName.toLowerCase() === 'a'

	return !(
		(event.target && (event?.target as HTMLElement).isContentEditable)
		|| event.defaultPrevented
		|| (isLink && event.which > 1)
		|| (isLink && event.altKey)
		|| (isLink && event.ctrlKey)
		|| (isLink && event.metaKey)
		|| (isLink && event.shiftKey)
	)
}
