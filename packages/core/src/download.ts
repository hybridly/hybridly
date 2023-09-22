import type { AxiosResponse } from 'axios'

/** Checks if the response wants to redirect to an external URL. */
export function isDownloadResponse(response: AxiosResponse): boolean {
	return response.status === 200 && !!response.headers['content-disposition']
}

/** Handles a download. */
export async function handleDownloadResponse(response: AxiosResponse) {
	const blob = new Blob([response.data], { type: 'application/octet-stream' })
	const urlObject = window.webkitURL || window.URL
	const link = document.createElement('a')
	link.style.display = 'none'
	link.href = urlObject.createObjectURL(blob)
	link.download = getFileNameFromContentDispositionHeader(response.headers['content-disposition'])
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
