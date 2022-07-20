import { debug } from '@monolikit/utils'
import type { AxiosResponse } from 'axios'
import type { InternalRouterContext } from '../context'
import type { NavigationOptions, VisitOptions, Errors, Progress, VisitPayload } from '../router'
import { CancellableEventFunction, createEventHook, EventFunction, EventHook, InternalEventHook } from './hook'

export interface EventMap {
	before: VisitOptions
	start: InternalRouterContext
	data: AxiosResponse
	progress: Progress
	success: VisitPayload
	error: Errors
	abort: InternalRouterContext
	invalid: AxiosResponse
	exception: Error
	fail: InternalRouterContext
	after: InternalRouterContext
	navigate: NavigationOptions
}

export const events = {
	before: createEventHook() as EventHook<EventMap['before']>,
	start: createEventHook() as EventHook<EventMap['start']>,
	data: createEventHook() as EventHook<EventMap['data']>,
	progress: createEventHook() as EventHook<EventMap['progress']>,
	success: createEventHook() as EventHook<EventMap['success']>,
	error: createEventHook() as EventHook<EventMap['error']>,
	abort: createEventHook() as EventHook<EventMap['abort']>,
	invalid: createEventHook() as EventHook<EventMap['invalid']>,
	exception: createEventHook() as EventHook<EventMap['exception']>,
	fail: createEventHook() as EventHook<EventMap['fail']>,
	after: createEventHook() as EventHook<EventMap['after']>,
	navigate: createEventHook() as EventHook<EventMap['navigate']>,
}

/** Triggers an event. */
export function triggerEvent<Event extends keyof EventMap, Parameter = EventMap[Event]>(
	event: Event,
	parameter: Parameter,
	fn?: EventFunction<Parameter>,
): Promise<boolean> {
	debug.event(`Triggering event [${event}] with parameter:`, parameter)
	return (events[event] as InternalEventHook<Parameter>).trigger(parameter as any, fn as any)
}

export interface VisitEvents {
	before: CancellableEventFunction<EventMap['before']>
	start: EventFunction<EventMap['start']>
	data: EventFunction<EventMap['data']>
	progress: EventFunction<EventMap['progress']>
	success: EventFunction<EventMap['success']>
	error: EventFunction<EventMap['error']>
	abort: EventFunction<EventMap['abort']>
	invalid: EventFunction<EventMap['invalid']>
	exception: EventFunction<EventMap['exception']>
	fail: EventFunction<EventMap['fail']>
	after: EventFunction<EventMap['after']>
	navigate: EventFunction<EventMap['navigate']>
}
