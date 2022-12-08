import isEqual from 'lodash.isequal'
import type { Progress, UrlResolvable, HybridRequestOptions } from '@hybridly/core'
import type { DeepReadonly } from 'vue'
import { clone } from '@hybridly/utils'
import { computed, reactive, ref, toRaw, watch } from 'vue'
import { router } from '@hybridly/core'
import { state } from '../stores/state'

type Fields = Record<string, any>

interface FormOptions<T extends Fields> extends Omit<HybridRequestOptions, 'data' | 'url'> {
	fields: T
	url?: UrlResolvable | (() => UrlResolvable)
	key?: string | false
	timeout?: number
	reset?: boolean
	transform?: (fields: T) => Fields
}

function safeClone<T>(obj: T): T {
	return clone(toRaw(obj))
}

export function useForm<T extends Fields = Fields>(options: FormOptions<T>) {
	// https://github.com/hybridly/hybridly/issues/23
	// TODO: explore unique/automatic key generation
	const shouldRemember = !!options?.key
	const historyKey = options?.key as string ?? 'form:default'
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
	const fields = reactive<T>(safeClone(historyData?.fields ?? options.fields))
	/** Validation errors for each field. */
	const errors = ref<Record<keyof T, string>>(historyData?.errors ?? {})
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

	/**
	 * Resets the form to its initial values.
	 */
	function reset(...keys: (keyof T)[]) {
		if (keys.length === 0) {
			keys = Object.keys(fields)
		}

		keys.forEach((key) => {
			Reflect.set(fields, key, safeClone(Reflect.get(initial, key)))
			clearError(key)
		})
	}

	/**
	 * Submits the form.
	 */
	function submit(optionsOverrides?: Omit<HybridRequestOptions, 'data'>) {
		const url = typeof options.url === 'function'
			? options.url()
			: options.url

		const data = typeof options.transform === 'function'
			? options.transform?.(fields)
			: fields

		const preserveState = optionsOverrides?.preserveState ?? options.preserveState

		return router.navigate({
			...options,
			url: url ?? state.context.value?.url,
			method: options.method ?? 'POST',
			...optionsOverrides,
			data: safeClone(data),
			preserveState: preserveState === undefined && options.method !== 'GET'
				? true
				: preserveState,
			hooks: {
				before: (navigation, context) => {
					failed.value = false
					successful.value = false
					recentlySuccessful.value = false
					clearTimeout(timeoutIds.recentlySuccessful!)
					clearTimeout(timeoutIds.recentlyFailed!)
					clearErrors()
					return options.hooks?.before?.(navigation, context)
				},
				start: (context) => {
					processing.value = true
					return options.hooks?.start?.(context)
				},
				progress: (incoming, context) => {
					progress.value = incoming
					return options.hooks?.progress?.(incoming, context)
				},
				error: (incoming, context) => {
					setErrors(incoming)
					failed.value = true
					recentlyFailed.value = true
					timeoutIds.recentlyFailed = setTimeout(() => recentlyFailed.value = false, options?.timeout ?? 5000)
					return options.hooks?.error?.(incoming, context)
				},
				success: (payload, context) => {
					if (options?.reset !== false) {
						reset()
					}
					successful.value = true
					recentlySuccessful.value = true
					timeoutIds.recentlySuccessful = setTimeout(() => recentlySuccessful.value = false, options?.timeout ?? 5000)
					return options.hooks?.success?.(payload, context)
				},
				after: (context) => {
					progress.value = undefined
					processing.value = false
					return options.hooks?.after?.(context)
				},
			},
		})
	}

	/**
	 * Clears all errors.
	 */
	function clearErrors(...keys: (keyof T)[]) {
		if (keys.length === 0) {
			keys = Object.keys(fields)
		}

		keys.forEach((key) => {
			clearError(key)
		})
	}

	/**
	 * Checks if the given keys are dirty in the form.
	 */
	function hasDirty(...keys: (keyof T)[]) {
		if (keys.length === 0) {
			return isDirty.value
		}

		return keys.some((key) => !isEqual(toRaw(fields[key]), toRaw(initial[key])))
	}

	/**
	 * Clears the given field's error.
	 */
	function clearError(key: keyof T) {
		errors.value[key] = undefined
	}

	/**
	 * Sets current errors.
	 */
	function setErrors(incoming: Record<string, string>) {
		errors.value = incoming
	}

	/**
	 * Aborts the submission.
	 */
	function abort() {
		router.abort()
	}

	watch([fields, processing, errors], () => {
		isDirty.value = !isEqual(toRaw(loaded), toRaw(fields))

		if (shouldRemember) {
			router.history.remember(historyKey, {
				fields: toRaw(fields),
				errors: toRaw(errors.value),
			})
		}
	}, { deep: true, immediate: true })

	return reactive({
		reset,
		fields,
		abort,
		setErrors,
		clearErrors,
		clearError,
		hasDirty,
		submitWithOptions: submit,
		submit: () => submit(),
		hasErrors: computed(() => Object.values(errors.value).length > 0),
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
