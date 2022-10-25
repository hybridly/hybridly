import { getHighlighter, createDiffProcessor, createFocusProcessor, createHighlightProcessor } from 'shiki-processor'
import { createVitepressProcessor } from './vitepress'

export default async function highlighter() {
	const highlighter = await getHighlighter({
		processors: [
			createDiffProcessor(),
			createFocusProcessor(),
			createHighlightProcessor(),
			createVitepressProcessor(),
		],
	})

	return (str: string, lang: string) => highlighter.codeToHtml(str, { lang })
}
