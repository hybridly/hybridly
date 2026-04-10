const directionSymbol = Symbol('direction')

interface InfoListOptions {
	direction: 'horizontal' | 'vertical'
}

export function defineInfoList(options: InfoListOptions) {
	provide(directionSymbol, options.direction)
}

export function useInfoList() {
	return {
		direction: inject(directionSymbol, 'horizontal'),
	}
}
