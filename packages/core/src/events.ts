import { AxiosResponse } from 'axios'
import { debug } from '@hybridly/utils'
import { RouterContext } from './router/context'
import { NavigationOptions, VisitOptions } from './router/router'
import { Errors, Progress, VisitPayload } from './types'

export interface VisitEvents {
	'before': (options: VisitOptions) => undefined | boolean
	'start': (context: RouterContext) => void
	'data': (response: AxiosResponse) => void
	'progress': (progress: Progress) => void
	'success': (payload: VisitPayload) => void
	'error': (errors: Errors) => void
	'abort': (context: RouterContext) => void
	'invalid': (response: AxiosResponse) => void
	'exception': (error: Error) => void
	'fail': (context: RouterContext) => void
	'after': (context: RouterContext) => void
	'navigate': (options: NavigationOptions) => void
}

/** Creates an emitter. */
export function createEmitter<Events extends EventsMap = DefaultEvents>(
	initial: PartialEvents<Events> = {},
): Emitter<Events> {
	const events = {
		initial,
		merged: {} as Partial<Events>,
	}

	function byKey<K extends keyof Events>(event: K): Events[K][] {
		return [
			events.merged[event],
			...events.initial[event] ?? [],
		].filter(Boolean) as Events[K][]
	}

	function cleanup() {
		debug.event('Cleaning up events.')
		events.merged = {}
	}

	return {
		events: byKey,
		cleanup,
		with: (merge?: Partial<Events>) => {
			if (merge) {
				debug.event(`Added handlers [${Object.keys(merge).join(', ')}] for this visit.`)
				events.merged = merge
			}
		},
		emit<K extends keyof Events>(event: K, ...args: Parameters<Events[K]>): boolean {
			debug.event(`Emitted [${event.toString()}] with`, ...args)

			return !byKey(event)
				.map((i) => i(...args))
				.some((r) => r !== undefined && !r)
		},
		on<K extends keyof Events>(event: K, cb: Events[K]): () => void {
			debug.event(`Added handler for [${event.toString()}].`)

			if (!events.initial[event]) {
				events.initial[event] = []
			}

			events.initial[event]!.push(cb)

			return () => (events.initial[event] = (events.initial[event] || [])!.filter((i) => i !== cb))
		},
	}
}

export interface Emitter<Events extends EventsMap = DefaultEvents> {
	cleanup(): void
	events<K extends keyof Events>(event: K): Events[K][]
	with(events?: Partial<VisitEvents>): void
	emit<K extends keyof Events>(event: K, ...args: Parameters<Events[K]>): boolean
	on<K extends keyof Events>(event: K, cb: Events[K]): () => void
}

interface EventsMap {
	[event: string]: any
}

interface DefaultEvents extends EventsMap {
	[event: string]: (...args: any) => void
}

type PartialEvents<Events> = Partial<{ [E in keyof Events]: Events[E][] }>
