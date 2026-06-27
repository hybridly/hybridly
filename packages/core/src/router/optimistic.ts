import { get as getPath, set as setPath } from 'es-toolkit/compat'
import { cloneDeep } from 'es-toolkit/object'
import { isEqual } from 'es-toolkit/predicate'
import { getInternalRouterContext, setContext } from '../context'
import type { InternalRouterContext } from '../context'
import type { MergeableProperty, OptimisticUpdateCallback, PendingHybridRequest, Properties, Property } from './types'

interface OptimisticLayer {
	requestId: string
	update: OptimisticUpdateCallback
	keys: string[]
}

export interface ViewPropertyState {
	component?: string
	committed: Properties
	rendered: Properties
	layers: OptimisticLayer[]
}

const propertyStates = new WeakMap<InternalRouterContext, ViewPropertyState>()

export function createViewPropertyState(component: string | undefined, properties: Properties): ViewPropertyState {
	return {
		component,
		committed: properties,
		rendered: properties,
		layers: [],
	}
}

export function beginOptimisticRequest(request: PendingHybridRequest): void {
	const update = request.options.updateImmediately

	if (!update) {
		return
	}

	const context = getInternalRouterContext()
	const state = ensureViewPropertyState()

	if (state.component !== context.view.component) {
		resetViewProperties(context.view.component, context.view.properties)
	}

	const patch = update(context.view.properties)

	if (!patch) {
		return
	}

	const keys = resolveTouchedKeys(context.view.properties, patch)

	if (keys.length === 0) {
		return
	}

	state.layers.push({
		requestId: request.id,
		update,
		keys,
	})

	propagateRenderedProperties(renderViewProperties())
}

export function resolveResponseMergeBase(
	request: PendingHybridRequest,
	mergeable: MergeableProperty[] = [],
	options: { includeOptimisticMergeables: boolean },
): Properties {
	const state = ensureViewPropertyState()
	const base = state.committed

	if (!options.includeOptimisticMergeables) {
		return base
	}

	const layer = findOptimisticLayer(request)

	if (!layer) {
		return base
	}

	let properties: Properties | undefined

	for (const [property] of mergeable) {
		if (layerTouchesProperty(layer, property)) {
			properties ??= cloneDeep(base)
			const renderedLayer = renderLayer(base, layer)
			setPath(properties, property, cloneDeep(getPath(renderedLayer, property)) as Property)
		}
	}

	return properties ?? base
}

export function commitResponseProperties(
	request: PendingHybridRequest,
	properties: Properties | undefined,
	options: { failed: boolean },
): Properties | undefined {
	const state = ensureViewPropertyState()
	const layer = findOptimisticLayer(request)

	removeOptimisticLayer(request)

	if (!properties) {
		state.rendered = renderViewProperties()
		return state.rendered
	}

	state.committed = options.failed && layer
		? restoreLayerKeys(properties, layer)
		: properties

	state.rendered = renderViewProperties()

	return state.rendered
}

export function rejectOptimisticRequest(request: PendingHybridRequest): void {
	const state = ensureViewPropertyState()

	if (!removeOptimisticLayer(request)) {
		return
	}

	propagateRenderedProperties(renderViewProperties())
	state.rendered = getInternalRouterContext().view.properties
}

export function resetViewProperties(component: string | undefined, properties: Properties): Properties {
	const context = getInternalRouterContext()
	const state = propertyStates.get(context) ?? createViewPropertyState(component, properties)

	state.component = component
	state.committed = properties
	state.layers = []
	state.rendered = properties
	propertyStates.set(context, state)

	return properties
}

export function getCommittedViewProperties(context: InternalRouterContext = getInternalRouterContext()): Properties {
	return ensureViewPropertyState(context).committed
}

function ensureViewPropertyState(context: InternalRouterContext = getInternalRouterContext()): ViewPropertyState {
	let state = propertyStates.get(context)

	if (!state) {
		state = createViewPropertyState(context.view.component, context.view.properties)
		propertyStates.set(context, state)
	}

	return state
}

function resolveTouchedKeys(currentProperties: Properties, update: Partial<Properties>): string[] {
	return Object
		.keys(update)
		.filter((key) => !isEqual(update[key], currentProperties[key]))
}

function findOptimisticLayer(request: PendingHybridRequest): OptimisticLayer | undefined {
	return ensureViewPropertyState().layers.find((layer) => layer.requestId === request.id)
}

function layerTouchesProperty(layer: OptimisticLayer, property: string): boolean {
	const rootProperty = property.split('.').at(0)

	return rootProperty !== undefined && layer.keys.includes(rootProperty)
}

function removeOptimisticLayer(request: PendingHybridRequest): boolean {
	const state = ensureViewPropertyState()
	const initialLength = state.layers.length
	state.layers = state.layers.filter((layer) => layer.requestId !== request.id)

	return state.layers.length !== initialLength
}

function renderViewProperties(base: Properties = ensureViewPropertyState().committed): Properties {
	return ensureViewPropertyState().layers.reduce(renderLayer, base)
}

function renderLayer(base: Properties, layer: OptimisticLayer): Properties {
	const patch = layer.update(base)

	if (!patch) {
		return base
	}

	return layer.keys.reduce<Properties>((properties, key) => {
		if (!Object.hasOwn(patch, key)) {
			return properties
		}

		properties[key] = patch[key] as Property
		return properties
	}, { ...base })
}

function restoreLayerKeys(properties: Properties, layer: OptimisticLayer): Properties {
	const restored = { ...properties }
	const committed = ensureViewPropertyState().committed

	for (const key of layer.keys) {
		if (Object.hasOwn(committed, key)) {
			restored[key] = cloneDeep(committed[key])
		} else {
			delete restored[key]
		}
	}

	return restored
}

function propagateRenderedProperties(properties: Properties): void {
	const context = getInternalRouterContext()
	const state = ensureViewPropertyState()

	state.rendered = properties

	setContext({
		view: {
			...context.view,
			properties,
		},
	}, { propagate: false })

	const updatedContext = getInternalRouterContext()

	updatedContext.adapter.onPropertiesUpdate?.(properties, updatedContext) ?? updatedContext.adapter.onContextUpdate?.(updatedContext)
}
