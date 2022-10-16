import { getHighlighter } from 'shiki'
import { postProcess, process, processors } from './processor'

export default async function highlighter() {
	const highlighter = await getHighlighter({})

	return (str: string, lang: string, attributes: string) => {
		const { code, lineOptions } = process(
			processors,
			str,
			lang = lang.replace(/-vue$/, '').toLowerCase(),
			attributes,
		)

		return postProcess(processors, highlighter.codeToHtml(code, { lang, lineOptions }), lang, attributes)
	}
}
