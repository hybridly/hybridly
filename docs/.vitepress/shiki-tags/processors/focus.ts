import { createRangeProcessor } from '../utils/range'
import type { Processor } from '../types'
import { addClass } from '../utils/add-class'

export default {
	name: 'focus',
	handler: createRangeProcessor({
		focus: ['has-focus'],
	}),
	postProcess: ({ code }) => {
		if (!code.includes('has-focus')) {
			return code
		}

		return addClass(code, 'has-focused-lines', 'pre')
	},
} as Processor
