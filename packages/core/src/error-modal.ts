type ErrorModalInput = { pageNotFound: string } | object | string
interface ErrorModalContext {
	response: ErrorModalInput
	main: HTMLHtmlElement
	overlay: HTMLDivElement
	iframe: HTMLIFrameElement
	hideOnEscape?: (event: KeyboardEvent) => void
}

/** @internal This function is meant to be used internally. */
export function showModal(htmlOrJson: ErrorModalInput) {
	return displayModal(createModal(htmlOrJson))
}

export function createModal(htmlOrJson: ErrorModalInput): ErrorModalContext {
	const html = getHtml(htmlOrJson)
	const main = document.createElement('html')
	main.innerHTML = html
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

function getHtml(htmlOrJson: ErrorModalInput): string {
	if (typeof htmlOrJson === 'string' && htmlOrJson.trim() === '') {
		return `
			${style()}
			<main>
				${icon()}
				<p class="error">The received response is empty and does not respect the hybridly protocol.</p>
			</main>
		`
	}

	if (typeof htmlOrJson === 'object' && 'pageNotFound' in htmlOrJson) {
		return `
			${style()}
			<main>
				${icon()}
				<p class="error">
					The received page component could not be found: <b>${htmlOrJson.pageNotFound}</b>.
				</p>
			</main>
		`
	}

	return htmlOrJson.toString()
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
			color: #ffffff;
			font-size: 1.25rem;
			font-family: Inter, "Inter var", -system-ui, "Segoe UI", Arial;
		}
		svg {
			heigth: 4rem;
			width: 4rem;
			color: #2f3d59;
			margin-bottom: 1rem;
		}
		p {
			margin: 0;
			font-weight: 500;
			text-align: center;
		}
		.error {
			color: #f4b1b1;
		}
		pre {
			margin-top: 2rem;
			color: white;
			font-size: 1rem;
		}
	</style>`
}

function icon() {
	return `
		<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
			<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
		</svg>
	`
}
