import type { HttpResponse } from './http'

/** Checks if the response wants to redirect to an external URL. */
export function isDownloadResponse(response: HttpResponse): boolean {
	return response.status === 200 && response.headers.has('content-disposition')
}

/** Handles a download. */
export async function handleDownloadResponse(response: HttpResponse) {
	const blob = response.toBlob()
	const urlObject = window.webkitURL || window.URL
	const link = document.createElement('a')
	link.style.display = 'none'
	link.href = urlObject.createObjectURL(blob)
	link.download = getFileNameFromContentDispositionHeader(response.headers.get('content-disposition') ?? '')
	link.click()
	setTimeout(() => {
		urlObject.revokeObjectURL(link.href)
		link.remove()
	}, 0)
}

function getFileNameFromContentDispositionHeader(header: string) {
	const result = header.split(';')[1]?.trim().split('=')[1]
	return result?.replace(/^"(.*)"$/, '$1') ?? ''
}
