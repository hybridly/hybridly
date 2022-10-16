import type { Processor } from '../types'

export default {
	name: 'autolink',
	postProcess: ({ code, attributes }) => {
		if (!attributes.includes('autolink')) {
			return
		}

		const urlRE = /https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&//=]*)/g

		return code.replaceAll(urlRE, (match) => `<a href="${match}">${match}</a>`)
	},
} as Processor
