import type { Path, SearchableObject } from '@clickbar/dot-diver'
import type { HybridRequestOptions, PendingHybridRequest, Progress, UrlResolvable } from '@hybridly/core'
import { router } from '@hybridly/core'
import { merge } from '@hybridly/utils'
import { get, set, unset } from 'es-toolkit/compat'
import { cloneDeep } from 'es-toolkit/object'
import { isEqual } from 'es-toolkit/predicate'
import type { DeepReadonly } from 'vue'
import { computed, reactive, ref, shallowRef, toRaw, watch } from 'vue'
import { formStore } from '../stores/form'
import { state } from '../stores/state'

type Errors<T extends SearchableObject> = {
	[K in keyof T]?: T[K] extends Record<string, any> ? Errors<T[K]>
		: string
}

type FormFieldBehavior = boolean | string[]

export type DefaultFormOptions = Pick<
	FormOptions<object>,
	| 'timeout'
	| 'resetOnSuccess'
	| 'resetOnError'
	| 'setDefaultOnSuccess'
	| 'progress'
	| 'preserveScroll'
	| 'preserveState'
	| 'preserveUrl'
	| 'headers'
	| 'errorBag'
	| 'spoof'
	| 'transformUrl'
	| 'updateHistoryState'
	| 'useFormData'
>

interface FormOptions<T extends SearchableObject> extends Omit<HybridRequestOptions, 'data' | 'url' | 'reset'> {
	fields: T
	url?: UrlResolvable | (() => UrlResolvable)
	key?: string | false
	/**
	 * Defines the delay after which the `recentlySuccessful` and `recentlyFailed` variables are reset to `false`.
	 */
	timeout?: number
	/**
	 * Resets the fields of the form to their default value after a successful submission.
	 * @default true
	 */
	resetOnSuccess?: FormFieldBehavior
	/**
	 * Resets the fields of the form to their default value after a failed submission.
	 * @default false
	 */
	resetOnError?: FormFieldBehavior
	/**
	 * Updates the default values from the form after a successful submission.
	 * @default false
	 */
	setDefaultOnSuccess?: FormFieldBehavior
	/**
	 * Callback executed before the form submission for transforming the fields.
	 */
	transform?: (fields: T) => any
}

export interface FormReturn<T extends SearchableObject, P extends Path<T> & string = Path<T> & string> {
	resetFields: (...keys: P[]) => void
	reset: () => void
	resetSubmissionState: () => void
	clear: (...keys: P[]) => void
	fields: T
	abort: () => void
	setErrors: (incoming: Errors<T>) => void
	clearErrors: (...keys: P[]) => void
	clearError: (key: P) => void
	setDefault: (newDefault: Partial<T>) => void
	hasDirty: (...keys: P[]) => boolean
	submit: (optionsOverrides?: Omit<FormOptions<T>, 'fields' | 'key'>) => Promise<any>
	hasErrors: boolean
	defaults: DeepReadonly<T>
	loaded: DeepReadonly<T>
	progress: Progress | undefined
	isDirty: boolean
	errors: Errors<T>
	processing: boolean
	successful: boolean
	failed: boolean
	recentlySuccessful: boolean
	recentlyFailed: boolean
}

function deepCloneRaw<T>(obj: T): T {
	return cloneDeep(toRaw(obj))
}

export function useForm<
	T extends SearchableObject,
	P extends Path<T> & string = Path<T> & string,
>(options: FormOptions<T>): FormReturn<T, P> {
	// https://github.com/hybridly/hybridly/issues/23
	// TODO: explore unique/automatic key generation
	const shouldRemember = !!options.key
	const historyKey = options.key as string ?? 'form:default'
	const historyData = shouldRemember ? router.history.get(historyKey) as any : undefined
	const timeoutIds = {
		recentlyFailed: undefined as ReturnType<typeof setTimeout> | undefined,
		recentlySuccessful: undefined as ReturnType<typeof setTimeout> | undefined,
	}

	/** Fields that were initially set up. */
	const defaults = ref(deepCloneRaw(options.fields))
	/** Fields as they were when loaded. */
	const loaded = deepCloneRaw(historyData?.fields ?? options.fields)
	/** Current fields. */
	const fields = reactive<T>(deepCloneRaw(loaded)) as T
	/** Validation errors for each field. */
	const errors = ref<Errors<T>>(historyData?.errors ?? {})
	/** Whether the form is dirty. */
	const isDirty = ref(false)
	/** Whether the submission was recently successful. */
	const recentlySuccessful = ref(false)
	/** Whether the submission is successful. */
	const successful = ref(false)
	/** Whether the submission was recently failed. */
	const recentlyFailed = ref(false)
	/** Whether the submission is failed. */
	const failed = ref(false)
	/** Whether the submission is being processed. */
	const processing = ref(false)
	/** The current request's progress. */
	const progress = ref<Progress>()
	/** The current request. */
	const request = shallowRef<PendingHybridRequest>()
	/** Abort controller for the current request. */
	let abortController: AbortController | undefined

	/**
	 * Sets new default values for the form, so subsequent resets will use these values.
	 */
	function setDefault(newDefault: Partial<T>) {
		Object.entries(newDefault).forEach(([key, value]) => {
			set(defaults.value, key, deepCloneRaw(value))
		})
	}

	function resolveFieldBehaviorKeys(option: FormFieldBehavior | undefined, fallback: boolean): P[] | undefined {
		if (option === false) {
			return undefined
		}

		if (Array.isArray(option)) {
			return option.length > 0 ? option as P[] : undefined
		}

		if (option === true) {
			return Object.keys(fields) as P[]
		}

		return fallback ? Object.keys(fields) as P[] : undefined
	}

	function setDefaultFromFields(option: FormFieldBehavior | undefined) {
		const keys = resolveFieldBehaviorKeys(option, false)

		if (!keys) {
			return
		}

		keys.forEach((key) => {
			set(defaults.value, key, deepCloneRaw(get(fields, key)))
		})
	}

	function resetFieldsFromBehavior(option: FormFieldBehavior | undefined, fallback: boolean) {
		const keys = resolveFieldBehaviorKeys(option, fallback)

		if (!keys) {
			return
		}

		resetFields(...keys)
	}

	/**
	 * Resets the form's failed and successful flags.
	 */
	function resetSubmissionState() {
		successful.value = false
		failed.value = false
		recentlyFailed.value = false
		recentlySuccessful.value = false
		clearTimeout(timeoutIds.recentlySuccessful!)
		clearTimeout(timeoutIds.recentlyFailed!)
		progress.value = undefined
	}

	/**
	 * Resets the fields, errors and submission state.
	 */
	function reset() {
		resetSubmissionState()
		clearErrors()
		resetFields()
	}

	/**
	 * Resets the fields to their default values.
	 */
	function resetFields(...keys: P[]) {
		if (keys.length === 0) {
			keys = Object.keys(fields) as P[]
		}

		keys.forEach((key) => {
			set(fields, key, deepCloneRaw(get(defaults.value, key)))
		})
	}

	/**
	 * Clear the form fields.
	 */
	function clear(...keys: P[]) {
		if (keys.length === 0) {
			keys = Object.keys(fields) as P[]
		}

		keys.forEach((key) => {
			delete (fields as any)[key]
		})
	}

	/**
	 * Submits the form.
	 */
	function submit(optionsOverrides?: Omit<FormOptions<T>, 'fields' | 'key'>) {
		const { fields: _f, key: _k, ...optionsWithoutFields } = options
		const resolvedOptions = optionsOverrides
			? merge(optionsWithoutFields, optionsOverrides, { mergePlainObjects: true })
			: optionsWithoutFields

		const optionsWithOverrides = merge<FormOptions<T>>(formStore.getDefaultConfig(), resolvedOptions, { mergePlainObjects: true })
		const {
			timeout,
			resetOnSuccess,
			resetOnError,
			setDefaultOnSuccess,
			transform,
			...requestOptions
		} = optionsWithOverrides

		const url = typeof requestOptions.url === 'function'
			? requestOptions.url()
			: requestOptions.url

		const data = typeof transform === 'function'
			? transform(fields)
			: fields

		const preserveState = requestOptions.preserveState ?? requestOptions.method !== 'GET'
		const hooks = requestOptions.hooks ?? {}

		abortController = requestOptions.abortController ?? new AbortController()

		return router.navigate({
			...requestOptions,
			abortController,
			url: url ?? state.context.value?.url,
			method: requestOptions.method ?? 'POST',
			data: deepCloneRaw(data),
			preserveState,
			hooks: {
				before: (_request, context) => {
					request.value = _request
					resetSubmissionState()

					return hooks.before?.(_request, context)
				},
				start: (request, context) => {
					processing.value = true

					return hooks.start?.(request, context)
				},
				progress: (incoming, request, context) => {
					progress.value = {
						event: incoming,
						percentage: incoming.percentage,
					}

					return hooks.progress?.(incoming, request, context)
				},
				'validation-error': (incoming, request, context) => {
					setErrors(incoming)
					resetFieldsFromBehavior(resetOnError, false)
					failed.value = true
					recentlyFailed.value = true
					timeoutIds.recentlyFailed = setTimeout(() => recentlyFailed.value = false, timeout ?? 5000)

					return hooks['validation-error']?.(incoming, request, context)
				},
				success: (payload, request, response, context) => {
					clearErrors()
					setDefaultFromFields(setDefaultOnSuccess)
					resetFieldsFromBehavior(resetOnSuccess, true)

					successful.value = true
					recentlySuccessful.value = true
					timeoutIds.recentlySuccessful = setTimeout(() => recentlySuccessful.value = false, timeout ?? 5000)

					return hooks.success?.(payload, request, response, context)
				},
				after: (_request, context) => {
					request.value = undefined
					progress.value = undefined
					processing.value = false

					return hooks.after?.(_request, context)
				},
			},
		})
	}

	/**
	 * Clears all errors.
	 */
	function clearErrors(...keys: P[]) {
		if (keys.length === 0) {
			keys = Object.keys(fields) as P[]
		}

		keys.forEach((key) => {
			clearError(key)
		})
	}

	/**
	 * Checks if the given keys are dirty in the form.
	 */
	function hasDirty(...keys: P[]) {
		if (keys.length === 0) {
			return isDirty.value
		}

		return keys.some((key) => !isEqual(toRaw(get(fields, key)), toRaw(get(defaults.value, key))))
	}

	/**
	 * Clears the given field's error.
	 */
	function clearError(key: P) {
		unset(errors.value, key)
	}

	/**
	 * Sets current errors.
	 */
	function setErrors(incoming: Errors<T>) {
		clearErrors()
		Object.entries(incoming).forEach(([path, value]) => {
			set(errors.value, path, value)
		})
	}

	/**
	 * Aborts the submission.
	 */
	function abort() {
		abortController?.abort()
	}

	watch([fields, processing, errors], () => {
		isDirty.value = !isEqual(toRaw(defaults.value), toRaw(fields))

		if (shouldRemember) {
			router.history.remember(historyKey, {
				fields: toRaw(fields),
				errors: toRaw(errors.value),
			})
		}
	}, { deep: true, immediate: true })

	return reactive({
		resetFields,
		reset,
		resetSubmissionState,
		clear,
		fields,
		abort,
		setErrors,
		clearErrors,
		clearError,
		setDefault,
		hasDirty,
		submit,
		hasErrors: computed(() => Object.values(errors.value ?? {}).length > 0),
		defaults: defaults as DeepReadonly<typeof defaults>,
		loaded: loaded as DeepReadonly<typeof loaded>,
		progress: progress as DeepReadonly<typeof progress>,
		isDirty: isDirty as DeepReadonly<typeof isDirty>,
		errors: errors as DeepReadonly<typeof errors>,
		processing: processing as DeepReadonly<typeof processing>,
		successful: successful as DeepReadonly<typeof successful>,
		failed: failed as DeepReadonly<typeof failed>,
		recentlySuccessful: recentlySuccessful as DeepReadonly<typeof recentlySuccessful>,
		recentlyFailed: recentlyFailed as DeepReadonly<typeof recentlyFailed>,
	}) as FormReturn<T, P>
}
