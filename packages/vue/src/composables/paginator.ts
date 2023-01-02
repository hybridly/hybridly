// Credits to Boris Lepikhin
// See: https://github.com/lepikhinb/momentum-paginator/blob/master/src/index.ts
// Twitter: https://twitter.com/lepikhinb

declare global {
	/**
	 * Paginated data with metadata in a `meta` wrap.
	 */
	interface Paginator<T = any> {
		data: T[]
		meta: PaginatorMeta
		links: PaginatorLink[]
	}

	/**
	 * Paginated data without metadata wrapping.
	 */
	interface UnwrappedPaginator<T = any> extends PaginatorMeta {
		data: T[]
		links: PaginatorLink[]
	}
}

interface PaginatorLink {
	url?: string
	label: string
	active: boolean
}

interface PaginatorMeta {
	path: string
	from: number
	to: number
	total: number
	per_page: number
	current_page: number
	last_page: number
	first_page_url: string
	last_page_url: string
	next_page_url: string | undefined
	prev_page_url: string | undefined
	links?: PaginatorLink[]
}

interface Item {
	url: string | undefined
	label: string
	isPage: boolean
	isActive: boolean
	isPrevious: boolean
	isNext: boolean
	isCurrent: boolean
	isSeparator: boolean
}

export function usePaginator<T = any>(paginator: UnwrappedPaginator<T> | Paginator<T> | PaginatorMeta) {
	const meta = (paginator as Paginator<T>).meta ?? (paginator as PaginatorMeta)
	const links = meta.links ?? paginator.links!

	const items = links.map((link, index) => {
		return {
			url: link.url,
			label: link.label,
			isPage: !isNaN(+link.label),
			isPrevious: index === 0,
			isNext: index === links.length - 1,
			isCurrent: link.active,
			isSeparator: link.label === '...',
			isActive: !!link.url && !link.active,
		}
	}) as Item[]

	const pages: Item[] = items.filter((item) => item.isPage || item.isSeparator)
	const current = items.find((item) => item.isCurrent)
	const previous = items.find((item) => item.isPrevious)!
	const next = items.find((item) => item.isNext)!
	const first = { ...items[1], isActive: items[1].url !== current?.url, label: '&laquo;' }
	const last = { ...items[items.length - 1], isActive: items[items.length - 1].url !== current?.url, label: '&raquo;' }
	const from = meta.from
	const to = meta.to
	const total = meta.total

	return { pages, items, previous, next, first, last, total, from, to }
}
