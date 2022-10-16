import { createRangeProcessor } from '../utils/range'
import type { Processor } from '../types'

export default {
	name: 'highlight',
	handler: createRangeProcessor({
		hl: ['highlight'],
	}),
} as Processor
