import qs from 'qs'
import defu from 'defu'
import { h, defineComponent, PropType } from 'vue'
import { debug, makeUrl, Method } from '@sleightful/core'
import { router } from '../router'

export const Link = defineComponent({
	name: 'Link',
	setup(_, { slots, attrs }) {
		return (props: typeof _) => {
			let data = props.data
			const url = makeUrl(props.href ?? '')
			const as = props.as.toLowerCase()
			const method = props.method.toUpperCase() as Method

			// When performing a GET request, we want to move all the data into the URL
			// query string.
			if (method === 'GET') {
				debug.adapter('vue', 'Moving data object to URL parameters.')
				url.search = qs.stringify(defu(data, qs.parse(url.search, { ignoreQueryPrefix: true })), {
					encodeValuesOnly: true,
					arrayFormat: 'brackets',
				})
				data = {}
			}

			if (as === 'a' && method !== 'GET') {
				debug.adapter('vue', `Creating POST/PUT/PATCH/DELETE <a> links is discouraged as it causes "Open Link in New Tab/Window" accessibility issues.\n\nPlease specify a more appropriate element using the "as" attribute. For example:\n\n<Link href="${url}" method="${method}" as="button">...</Link>`)
			}

			return h(props.as, {
				...attrs,
				...as === 'a' ? { href: url } : {},
				...props.disabled ? { disabled: props.disabled } : {},
				onClick: (event: KeyboardEvent) => {
					// If the target is external, we don't want sleightful to handle the
					// navigation, so we return early to avoid preventing the event.
					if (props.external) {
						return
					}

					if (!shouldIntercept(event)) {
						return
					}

					event.preventDefault()

					if (props.disabled) {
						return
					}

					router.visit({
						url,
						data,
						method,
						replace: props.replace,
						preserveScroll: props.preserveScroll,
						preserveState: props.preserveState ?? (method !== 'GET'),
						only: props.only as any, // TODO fix typings there
						except: props.except as any,
						headers: props.headers,
					})
				},
			}, slots)
		}
	},
	props: {
		as: {
			type: String,
			default: 'a',
		},
		data: {
			type: Object as PropType<Record<string, any>>,
			default: () => ({}),
		},
		disabled: {
			type: Boolean,
			default: false,
		},
		href: {
			type: String,
			default: undefined,
		},
		method: {
			type: String,
			default: 'GET',
		},
		replace: {
			type: Boolean,
			default: false,
		},
		preserveScroll: {
			type: Boolean,
			default: false,
		},
		preserveState: {
			type: Boolean,
			default: null,
		},
		only: {
			type: Array as PropType<string[]>,
		},
		except: {
			type: Array as PropType<string[]>,
		},
		headers: {
			type: Object as PropType<Record<string, any>>,
		},
		external: {
			type: Boolean,
			default: false,
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
