const stack: string[] = []

class Modal {
	private main!: HTMLHtmlElement
	private overlay!: HTMLDivElement
	private iframe!: HTMLIFrameElement
	private style!: HTMLStyleElement
	private hideOnEscape?: (event: KeyboardEvent) => void
	private animationDurationInMs: number = 200

	constructor(private html: string, private id: string) {
		if (stack.includes(id)) {
			return
		}

		if (this.initializeDOM() === false) {
			return
		}

		this.show()
	}

	static fromException(response: string, id: string) {
		if (typeof response === 'string' && response.trim() !== '') {
			return new Modal(`<style>${htmlStyle()}</style>${response.toString()}`, id)
		}

		return new Modal(`
			<style>${style()}</style>
			<div class="h-full text-center flex">
				<div class="m-auto">
					<div class="text-5xl font-thin">Error</div>
					<div class="opacity-30 text-lg font-thin m-1">The received response does not respect the Hybridly protocol.</div>
					<pre class="text-sm opacity-80 max-h-[500px] w-full mx-auto text-left mt-6">${JSON.stringify(response, null, 2)}</pre>
				</div>
			</div>
		`, id)
	}

	static forPageComponent(component: string, id: string) {
		return new Modal(`
			<style>${style()}</style>
			<div class="h-full text-center flex">
				<div class="m-auto">
					<div class="text-5xl font-thin">Error</div>
					<div class="opacity-30 text-lg font-thin m-1">The specified page component does not exist.</div>
					<div class="m-2 flex justify-center text-xl opacity-30 underline underline-dotted">${component}</div>
				</div>
			</div>
		`, id)
	}

	static domainsDisabled(component: string, id: string) {
		return new Modal(`
			<style>${style()}</style>
			<div class="h-full text-center flex">
				<div class="m-auto">
					<div class="text-5xl font-thin">Error</div>
					<div class="opacity-30 text-lg font-thin m-1">
						A domain-based page component was specificed, but domains are disabled. <br />
						Set <code>domains</code> to <code>true</code> in <a class="underline underline-dotted" href="https://hybridly.dev/configuration/architecture.html#domains"><code>hybridly.config.ts</code></a>.
					</div>
					<div class="m-2 flex justify-center text-xl opacity-30 underline underline-dotted">${component}</div>
				</div>
			</div>
		`, id)
	}

	initializeDOM() {
		if (!this.html) {
			return false
		}

		if (document.querySelector('[data-hybridly-overlay="true"]')) {
			return false
		}

		const main = document.createElement('html')
		main.innerHTML = this.html
		main.querySelectorAll('a').forEach((a) => a.setAttribute('target', '_top'))

		const overlay = document.createElement('div')
		overlay.dataset.hybridly = ''
		overlay.style.position = 'fixed'
		overlay.style.width = '100vw'
		overlay.style.height = '100vh'
		overlay.style.padding = '50px'
		overlay.style.boxSizing = 'border-box'
		overlay.style.backgroundColor = 'rgba(0, 0, 0, .35)'
		overlay.style.color = 'white'
		overlay.style.zIndex = '2147483638'
		overlay.style.overflow = 'hidden'

		const iframe = document.createElement('iframe')
		iframe.style.backgroundColor = '#050505'
		iframe.style.width = '100%'
		iframe.style.height = '100%'
		iframe.style.borderRadius = '10px'

		overlay.appendChild(iframe)

		const style = document.createElement('style')
		style.innerHTML = `
			[data-hybridly] {
				opacity: 0;
				overflow: hidden;
				transition: opacity ${this.animationDurationInMs}ms ease-out;
			}

			[data-hybridly="visible"] {
				opacity: 1;
			}

			[data-hybridly] iframe {
				box-shadow: 0px 10px 35px 5px rgba(0,0,0,0.2);
				opacity: 0;
				overflow: hidden;
				transform: scale(.85);
				transition: all 100ms ease-out;
			}
			
			[data-hybridly="visible"] iframe {
				transform: scale(1);
				opacity: 1;
			}
		`

		this.main = main
		this.overlay = overlay
		this.iframe = iframe
		this.style = style
	}

	show() {
		stack.push(this.id)

		this.overlay.addEventListener('click', () => this.destroy())
		this.hideOnEscape = (event: KeyboardEvent) => {
			if (event.keyCode === 27) {
				this.destroy()
			}
		}

		document.addEventListener('keydown', this.hideOnEscape)
		// document.body.style.overflow = 'hidden'
		document.body.prepend(this.style)
		document.body.prepend(this.overlay)

		this.iframe.contentWindow?.document.open()
		this.iframe.contentWindow?.document.write(this.main.outerHTML)
		this.iframe.contentWindow?.document.close()
		this.overlay.dataset.hybridly = 'visible'
	}

	destroy() {
		stack.splice(stack.indexOf(this.html), 1)
		this.overlay.dataset.hybridly = ''
		setTimeout(() => {
			this.overlay.outerHTML = ''
			this.overlay.remove()
			this.style.remove()
			// document.body.style.overflow = 'visible'
			document.removeEventListener('keydown', this.hideOnEscape!)
		}, this.animationDurationInMs)
	}
}

export function showResponseErrorModal(response: string): Modal {
	return Modal.fromException(response, 'non-hybrid-response')
}

export function showPageComponentErrorModal(response: string): Modal {
	return Modal.forPageComponent(response, `page-component-${response}`)
}

export function showDomainsDisabledErrorModal(response: string): Modal {
	return Modal.domainsDisabled(response, `domains-disabled-${response}`)
}

function htmlStyle() {
	return `
		html {
			background-color: #050505;
			color: white;
			font-family: ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,Segoe UI,Roboto,Helvetica Neue,Arial,Noto Sans,sans-serif,"Apple Color Emoji","Segoe UI Emoji",Segoe UI Symbol,"Noto Color Emoji";
			display: flex;
			flex-direction: column;
			height: 100%;
		}

		a {
			color: white;
		}
	`
}

function style() {
	return `
		${htmlStyle()}
		body {
			padding: 5rem 2rem;
			flex-grow: 1;
		}
		.m-1 {
			margin: 0.25rem;
		}
		.m-2 {
			margin: 0.5rem;
		}
		.mt-6 {
			margin-top: 1.5rem;
		}
		.m-auto {
			margin: auto;
		}
		.h-full {
			height: 100%;
		}
		.max-h-\[500px\] {
			max-height: 500px;
		}
		.w-full {
			width: 100%;
		}
		.flex {
			display: flex;
		}
		.justify-center {
			justify-content: center;
		}
		.text-center {
			text-align: center;
		}
		.text-left {
			text-align: left;
		}
		.text-5xl {
			font-size: 3rem;
			line-height: 1;
		}
		.text-lg {
			font-size: 1.125rem;
			line-height: 1.75rem;
		}
		.text-xl {
			font-size: 1.25rem;
			line-height: 1.75rem;
		}
		.text-sm {
			font-size: 0.875rem;
			line-height: 1.25rem;
		}
		.font-thin {
			font-weight: 100;
		}
		.underline {
			text-decoration-line: underline;
		}
		.underline-dotted {
			text-decoration-style: dotted;
		}
		.opacity-30 {
			opacity: 0.3;
		}
		.opacity-80 {
			opacity: 0.8;
		}
	`
}
