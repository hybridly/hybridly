type Response = Record<string, unknown> | string
interface ErrorModalContext {
	response: Response
	main: HTMLHtmlElement
	overlay: HTMLDivElement
	iframe: HTMLIFrameElement
	hideOnEscape?: (event: KeyboardEvent) => void
}

export function createModal(htmlOrJson: Record<string, unknown> | string): ErrorModalContext {
	if (typeof htmlOrJson === 'object') {
		htmlOrJson = `
			<style>
				pre {
					color: white
				}
			</style>
			<pre>${JSON.stringify(htmlOrJson)}</pre>
		`
	}

	const main = document.createElement('html')
	main.innerHTML = htmlOrJson
	main.querySelectorAll('a').forEach((a) => a.setAttribute('target', '_top'))

	const overlay = document.createElement('div')
	overlay.style.position = 'fixed'
	overlay.style.width = '100vw'
	overlay.style.height = '100vh'
	overlay.style.padding = '50px'
	overlay.style.boxSizing = 'border-box'
	overlay.style.backgroundColor = 'rgba(0, 0, 0, .6)'
	overlay.style.zIndex = '99999'

	const iframe = document.createElement('iframe')
	iframe.style.backgroundColor = '#111827'
	iframe.style.borderRadius = '5px'
	iframe.style.width = '100%'
	iframe.style.height = '100%'

	overlay.appendChild(iframe)

	return {
		main,
		overlay,
		iframe,
		response: htmlOrJson,
	}
}

export function displayModal(context: ErrorModalContext) {
	context.overlay.addEventListener('click', () => destroyModal(context))
	context.hideOnEscape = (event: KeyboardEvent) => {
		if (event.keyCode === 27) {
			destroyModal(context)
		}
	}

	document.addEventListener('keydown', context.hideOnEscape)
	document.body.prepend(context.overlay)
	document.body.style.overflow = 'hidden'

	context.iframe.contentWindow?.document.open()
	context.iframe.contentWindow?.document.write(context.main.outerHTML)
	context.iframe.contentWindow?.document.close()
}

export function destroyModal(context: ErrorModalContext) {
	context.overlay.outerHTML = ''
	context.overlay.remove()
	document.body.style.overflow = 'visible'
	document.removeEventListener('keydown', context.hideOnEscape!)
}
