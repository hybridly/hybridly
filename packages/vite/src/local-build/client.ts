export function getClientCode() {
	const style = `
		.local-build {
			position: fixed;
			bottom: 1rem;
			left: 1rem;
			z-index: 50;
			display: inline-flex;
			max-width: 26rem;
			align-items: center;
			background-color: rgba(0, 0, 0, 0.9);
			padding: 0.75rem;
			font-size: 0.75rem;
			color: #8C8C8C;
			transition: opacity 0.3s;
		}

		.local-build:hover {
			opacity: 0.1;
		}

		.local-build .icon {
			margin-right: 0.5rem;
			height: 1.25rem;
			width: 1.25rem;
			fill: currentColor;
		}

		.local-build .content {
			display: inline-flex;
			flex-direction: column;
			gap: 0.5rem;
		}

		.local-build .title {
			font-weight: 500;
		}
	`
	const html = `
		<div class="local-build">
		<svg viewBox="0 0 24 24" width="1.2em" height="1.2em" class="icon"><path fill="currentColor" d="M11 15h2v2h-2zm0-8h2v6h-2zm1-5C6.47 2 2 6.5 2 12a10 10 0 0 0 10 10a10 10 0 0 0 10-10A10 10 0 0 0 12 2m0 18a8 8 0 0 1-8-8a8 8 0 0 1 8-8a8 8 0 0 1 8 8a8 8 0 0 1-8 8"></path></svg>
			<span class="content">
					<span class="title">This is a local production build. Changes will not be reflected.</span>
			</span>
		</div>
		`

	// inject the above style and html in the page
	return `
		;(function() {
			const style = document.createElement('style')
			style.innerHTML = \`${style}\`
			document.head.appendChild(style)

			const html = document.createElement('div')
			html.innerHTML = \`${html}\`
			document.body.appendChild(html)
			html.addEventListener('click', () => html.remove())
		})()
	`
}
