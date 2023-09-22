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
	 * Simple-paginated data with metadata in a `meta` wrap.
	 */
	interface SimplePaginator<T = any> {
		data: T[]
		meta: SimplePaginatorMeta
	}

	/**
	 * Cursor paginator.
	 */
	interface CursorPaginator<T = any> {
		data: T[]
		meta: CursorPaginatorMeta
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

interface CursorPaginatorMeta {
	path: string
	per_page: number
	previous_cursor: string
	next_cursor: string
	next_page_url?: string
	previous_page_url?: string
}

interface SimplePaginatorMeta {
	path: string
	per_page: number
	current_page: number
	next_page_url?: string
	first_page_url: string
	prev_page_url?: string
	from: number
	to: number
}

interface PaginatorMeta {
	path: string
	from: number
	to: number
	total: number
	per_page: number
	current_page: number
	first_page: number
	last_page: number
	first_page_url: string
	last_page_url: string
	next_page_url?: string
	prev_page_url?: string
}

export {}
