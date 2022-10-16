import type { HtmlRendererOptions } from 'shiki'

export type LineOptions = NonNullable<HtmlRendererOptions['lineOptions']>

export interface ProcessorResult {
	code: string
	lineOptions: LineOptions
}

export type PostProcessorResult = string

export interface TagClassesDictionary {
	[tag: string]: string[]
}

export interface ProcessorOptions {
	code: string
	lang: string
	attributes: string
}

export type ProcessorHandler = (options: ProcessorOptions) => ProcessorResult
export type PostProcessorHandler = (options: ProcessorOptions) => PostProcessorResult

export interface Processor {
	name: string
	handler?: ProcessorHandler
	postProcess?: PostProcessorHandler
}
