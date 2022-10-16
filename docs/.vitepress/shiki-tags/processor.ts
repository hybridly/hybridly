import diff from './processors/diff'
import highlight from './processors/highlight'
import vitepress from './processors/vitepress'
import focus from './processors/focus'
import type { Processor, ProcessorResult } from './types'
import autolink from './processors/autolink'

export const processors: Processor[] = [
	diff,
	focus,
	highlight,
	autolink,
	vitepress,
]

/**
 * Transforms code through the given processors.
 */
export function process(processors: Processor[], code: string, lang: string, attributes: string = '') {
	return processors.reduce((options, processor) => {
		const { code, lineOptions } = processor.handler?.({
			code: options.code,
			lang,
			attributes,
		}) ?? options

		// console.log({ name: processor.name, before: options.code, code })

		return {
			code,
			lineOptions: [
				...options.lineOptions,
				...lineOptions,
			],
		}
	}, {
		code,
		lineOptions: [],
	} as ProcessorResult)
}

/**
 * Transforms final code through the given processors.
 */
export function postProcess(processors: Processor[], code: string, lang: string, attributes: string = '') {
	return processors.reduce((code, processor) => processor.postProcess?.({
		code,
		lang,
		attributes,
	}) ?? code, code)
}
