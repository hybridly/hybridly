import { RouterContext } from '@sleightful/core'
import nprogress from 'nprogress'

export function initializeProgress(context: RouterContext, options?: Partial<ProgressOptions>) {
	const resolved: ProgressOptions = {
		delay: 250,
		color: '#29d',
		includeCSS: true,
		spinner: false,
		...options,
	}

	nprogress.configure({ showSpinner: resolved.spinner })

	if (resolved.includeCSS) {
		injectCSS(resolved.color)
	}

	let timeout: ReturnType<typeof setTimeout>

	context.events.on('start', () => {
		timeout = setTimeout(() => nprogress.start(), resolved.delay)
	})

	context.events.on('progress', (progress) => {
		if (nprogress.isStarted() && progress.percentage) {
			nprogress.set(Math.max(nprogress.status!, progress.percentage / 100 * 0.9))
		}
	})

	context.events.on('success', () => nprogress.done())
	context.events.on('fail', () => {
		nprogress.set(0)
		nprogress.remove()
	})

	context.events.on('after', () => {
		clearTimeout(timeout)

		if (nprogress.isStarted()) {
			setTimeout(() => nprogress.remove(), 10)
		}
	})
}

function injectCSS(color: string) {
	const element = document.createElement('style')
	element.textContent = `
    #nprogress {
      pointer-events: none;
    }
    #nprogress .bar {
      background: ${color};
      position: fixed;
      z-index: 1031;
      top: 0;
      left: 0;
      width: 100%;
      height: 2px;
    }
    #nprogress .peg {
      display: block;
      position: absolute;
      right: 0px;
      width: 100px;
      height: 100%;
      box-shadow: 0 0 10px ${color}, 0 0 5px ${color};
      opacity: 1.0;
      -webkit-transform: rotate(3deg) translate(0px, -4px);
          -ms-transform: rotate(3deg) translate(0px, -4px);
              transform: rotate(3deg) translate(0px, -4px);
    }
    #nprogress .spinner {
      display: block;
      position: fixed;
      z-index: 1031;
      top: 15px;
      right: 15px;
    }
    #nprogress .spinner-icon {
      width: 18px;
      height: 18px;
      box-sizing: border-box;
      border: solid 2px transparent;
      border-top-color: ${color};
      border-left-color: ${color};
      border-radius: 50%;
      -webkit-animation: nprogress-spinner 400ms linear infinite;
              animation: nprogress-spinner 400ms linear infinite;
    }
    .nprogress-custom-parent {
      overflow: hidden;
      position: relative;
    }
    .nprogress-custom-parent #nprogress .spinner,
    .nprogress-custom-parent #nprogress .bar {
      position: absolute;
    }
    @-webkit-keyframes nprogress-spinner {
      0%   { -webkit-transform: rotate(0deg); }
      100% { -webkit-transform: rotate(360deg); }
    }
    @keyframes nprogress-spinner {
      0%   { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
  `
	document.head.appendChild(element)
}

export interface ProgressOptions {
	/**
	 * The delay after which the progress bar will
	 * appear during navigation, in milliseconds.
	 *
	 * @default 250
	 */
	delay: number

	/**
	 * The color of the progress bar.
	 *
	 * Defaults to #29d.
	 */
	color: string

	/**
	 * Whether to include the default NProgress styles.
	 *
	 * Defaults to true.
	 */
	includeCSS: boolean

	/**
	 * Whether the NProgress spinner will be shown.
	 *
	 * Defaults to false.
	 */
	spinner: boolean
}
