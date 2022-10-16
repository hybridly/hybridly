import { createRangeProcessor } from '../utils/range'
import type { Processor } from '../types'

export default {
	name: 'diff',
	handler: createRangeProcessor({
		'--': ['diff', 'remove'],
		'++': ['diff', 'add'],
	}),
} as Processor
