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
			${style()}
			<main>
			${icon()}
			<p>The received response do not respect the sleightful protocol.</p>
			<pre>${JSON.stringify(htmlOrJson, null, 2)}</pre>
		`
	} else if (htmlOrJson.trim() === '') {
		htmlOrJson = `
			${style()}
			<main>
				${icon()}
				<p>The received response is empty and do not respect the sleightful protocol.</p>
			</main>
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

function style() {
	return `
	<style>
		main {
			height: 100%;
			width: 100%;
			display: flex;
			flex-direction: column;
			justify-content: center;
			align-items: center;
			color: white;
			font-family: Inter, "Inter var", -system-ui, "Segoe UI";
			color: #374151;
		}
		svg {
			heigth: 3.5rem;
			width: 3.5rem;
		}
		p {
			margin-top: 0.25rem;
			font-weight: 600;
			text-align: center;
		}
		pre {
			margin-top: 0.25rem;
			color: white;
			font-size: 1rem;
		}
	</style>`
}

function icon() {
	return `
		<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
			<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
		</svg>
	`
}
