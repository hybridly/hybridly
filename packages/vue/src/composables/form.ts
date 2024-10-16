import isEqual from 'lodash.isequal'
import type { HybridRequestOptions, PendingHybridRequest, Progress, UrlResolvable } from '@hybridly/core'
import type { DeepReadonly } from 'vue'
import { computed, reactive, ref, shallowRef, toRaw, watch } from 'vue'
import { type Path, type SearchableObject, clone, getByPath, merge, setValueAtPath, unsetPropertyAtPath } from '@hybridly/utils'
import { router } from '@hybridly/core'
import { state } from '../stores/state'
import { formStore } from '../stores/form'

type Errors<T extends SearchableObject> = {
	[K in keyof T]?: T[K] extends Record<string, any>
		? Errors<T[K]>
		: string;
}

export type DefaultFormOptions = Pick<FormOptions<object>, 'timeout' | 'reset' | 'updateInitials' | 'progress' | 'preserveScroll' | 'preserveState' | 'preserveUrl' | 'headers' | 'errorBag' | 'spoof' | 'transformUrl' | 'updateHistoryState' | 'useFormData'>

interface FormOptions<T extends SearchableObject> extends Omit<HybridRequestOptions, 'data' | 'url'> {
	fields: T
	url?: UrlResolvable | (() => UrlResolvable)
	key?: string | false
	/**
	 * Defines the delay after which the `recentlySuccessful` and `recentlyFailed` variables are reset to `false`.
	 */
	timeout?: number
	/**
	 * Resets the fields of the form to their initial value after a successful submission.
	 * @default true
	 */
	reset?: boolean
	/**
	 * Updates the initial values from the form after a successful submission.
	 * @default false
	 */
	updateInitials?: boolean
	/**
	 * Callback executed before the form submission for transforming the fields.
	 */
	transform?: (fields: T) => any
}

function safeClone<T>(obj: T): T {
	return clone(toRaw(obj))
}

export function useForm<
	T extends SearchableObject,
	P extends Path<T> & string = Path<T> & string,
>(options: FormOptions<T>) {
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
	const initial = safeClone(options.fields)
	/** Fields as they were when loaded. */
	const loaded = safeClone(historyData?.fields ?? options.fields)
	/** Current fields. */
	const fields = reactive<T>(safeClone(loaded)) as T
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

	/**
	 * Sets new initial values for the form, so subsequent resets will use thse values.
	 */
	function setInitial(newInitial: Partial<T>) {
		Object.entries(newInitial).forEach(([key, value]) => {
			Reflect.set(initial, key, safeClone(value))
		})
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
	 * Resets the fields to their initial values.
	 */
	function resetFields(...keys: P[]) {
		if (keys.length === 0) {
			keys = Object.keys(fields) as P[]
		}

		keys.forEach((key) => {
			Reflect.set(fields, key, safeClone(Reflect.get(initial, key)))
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

		const url = typeof optionsWithOverrides.url === 'function'
			? optionsWithOverrides.url()
			: optionsWithOverrides.url

		const data = typeof optionsWithOverrides.transform === 'function'
			? optionsWithOverrides.transform(fields)
			: fields

		const preserveState = optionsWithOverrides.preserveState ?? optionsWithOverrides.method !== 'GET'
		const hooks = optionsWithOverrides.hooks ?? {}

		return router.navigate({
			...optionsWithOverrides,
			url: url ?? state.context.value?.url,
			method: optionsWithOverrides.method ?? 'POST',
			data: safeClone(data),
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
					progress.value = incoming
					return hooks.progress?.(incoming, request, context)
				},
				error: (incoming, request, context) => {
					setErrors(incoming)
					failed.value = true
					recentlyFailed.value = true
					timeoutIds.recentlyFailed = setTimeout(() => recentlyFailed.value = false, optionsWithOverrides.timeout ?? 5000)
					return hooks.error?.(incoming, request, context)
				},
				success: (payload, request, context) => {
					clearErrors()
					if (optionsWithOverrides.updateInitials) {
						setInitial(fields)
					}
					if (optionsWithOverrides.reset !== false) {
						resetFields()
					}
					successful.value = true
					recentlySuccessful.value = true
					timeoutIds.recentlySuccessful = setTimeout(() => recentlySuccessful.value = false, optionsWithOverrides.timeout ?? 5000)
					return hooks.success?.(payload, request, context)
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

		return keys.some((key) => !isEqual(toRaw(getByPath(fields, key)), toRaw(getByPath(initial, key))))
	}

	/**
	 * Clears the given field's error.
	 */
	function clearError(key: P) {
		unsetPropertyAtPath(errors.value, key)
	}

	/**
	 * Sets current errors.
	 */
	function setErrors(incoming: Errors<T>) {
		clearErrors()
		Object.entries(incoming).forEach(([path, value]) => {
			setValueAtPath(errors.value, path, value)
		})
	}

	/**
	 * Aborts the submission.
	 */
	function abort() {
		// TODO: cancel associated request
	}

	watch([fields, processing, errors], () => {
		isDirty.value = !isEqual(toRaw(initial), toRaw(fields))

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
		setInitial,
		hasDirty,
		submitWith: submit,
		/** @deprecated Use `submitWith` instead */
		submitWithOptions: submit,
		submit: () => submit(),
		hasErrors: computed(() => Object.values(errors.value ?? {}).length > 0),
		initial: initial as DeepReadonly<typeof initial>,
		loaded: loaded as DeepReadonly<typeof loaded>,
		progress: progress as DeepReadonly<typeof progress>,
		isDirty: isDirty as DeepReadonly<typeof isDirty>,
		errors: errors as DeepReadonly<typeof errors>,
		processing: processing as DeepReadonly<typeof processing>,
		successful: successful as DeepReadonly<typeof successful>,
		failed: failed as DeepReadonly<typeof failed>,
		recentlySuccessful: recentlySuccessful as DeepReadonly<typeof recentlySuccessful>,
		recentlyFailed: recentlyFailed as DeepReadonly<typeof recentlyFailed>,
	})
}
