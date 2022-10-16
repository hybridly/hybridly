import type { LineOptions, TagClassesDictionary, ProcessorHandler, ProcessorOptions } from '../types'

export function createRangeProcessor(dictionary: TagClassesDictionary): ProcessorHandler {
	return ({ code }: ProcessorOptions) => {
		// https://regex101.com/r/mUxvfx/1
		const tagRE = /(?:\/\/|\/\*{1,2}) *\[!vp ([\w+-]+)(?::(\d+))?] *(?:\*{1,2}\/)?/
		const lineOptions: LineOptions = []

		code = code
			.split('\n')
			.map((lineOfCode, lineNumber) => {
				const [match, tag, range] = lineOfCode.match(tagRE) ?? []

				if (!match) {
					return lineOfCode
				}

				if (!Object.keys(dictionary).includes(tag)) {
					return lineOfCode
				}

				Array.from({ length: Number(range ?? 1) }).forEach((_, rangeOffset) => {
					lineOptions.push({
						line: lineNumber + rangeOffset + 1,
						classes: dictionary[tag as keyof typeof dictionary],
					})
				})

				return lineOfCode.replace(tagRE, '')
			})
			.join('\n')

		return {
			code,
			lineOptions,
		}
	}
}
