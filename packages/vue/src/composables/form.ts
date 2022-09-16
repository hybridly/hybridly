import isEqual from 'lodash.isequal'
import clone from 'lodash.clone'
import type { UrlResolvable, VisitOptions } from '@hybridly/core'
import { computed, reactive, readonly, ref, toRaw, watch } from 'vue'
import { router } from '@hybridly/core'
import { state } from '../stores/state'

type Fields = Record<string, any>

interface FormOptions<T extends Fields> extends Omit<VisitOptions, 'data' | 'url'> {
	fields: T
	url?: UrlResolvable | (() => UrlResolvable)
	key?: string | false
	timeout?: number
	reset?: boolean
	transform?: (fields: T) => Fields
}

export function useForm<T extends Fields = Fields>(options: FormOptions<T>) {
	const shouldRemember = options?.key !== false
	const historyKey = options?.key as string ?? 'form:default'
	const historyData = shouldRemember ? router.history.get(historyKey) as any : undefined
	const timeoutIds = {
		recentlyFailed: undefined as ReturnType<typeof setTimeout> | undefined,
		recentlySuccessful: undefined as ReturnType<typeof setTimeout> | undefined,
	}

	/** Fields that were initially set up. */
	const initial = readonly(clone(options.fields))
	/** Fields as they were when loaded. */
	const loaded = readonly(clone(historyData?.fields ?? options.fields))
	/** Current fields. */
	const fields = reactive<T>(clone(historyData?.fields ?? options.fields))
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

	/**
	 * Resets the form to its initial values.
	 */
	function reset(...keys: (keyof T)[]) {
		keys ??= Object.keys(fields)
		keys.forEach((key) => Reflect.set(fields, key, Reflect.get(initial, key)))
		clearErrors()
	}

	/**
	 * Submits the form.
	 */
	function submit(optionsOverrides?: Omit<VisitOptions, 'data'>) {
		const url = typeof options.url === 'function'
			? options.url()
			: options.url

		const data = typeof options.transform === 'function'
			? options.transform?.(fields)
			: fields

		return router.visit({
			url: url ?? state.context.value?.url,
			method: options.method ?? 'POST',
			...optionsOverrides,
			data: clone(toRaw(data)),
			preserveState: optionsOverrides?.preserveState === undefined && options.method !== 'GET'
				? true
				: optionsOverrides?.preserveState,
			hooks: {
				before: (visit) => {
					failed.value = false
					successful.value = false
					recentlySuccessful.value = false
					clearTimeout(timeoutIds.recentlySuccessful!)
					clearTimeout(timeoutIds.recentlyFailed!)
					clearErrors()
					return options.hooks?.before?.(visit)
				},
				start: (context) => {
					processing.value = true
					return options.hooks?.start?.(context)
				},
				error: (incoming) => {
					setErrors(incoming)
					failed.value = true
					recentlyFailed.value = true
					timeoutIds.recentlyFailed = setTimeout(() => recentlyFailed.value = false, options?.timeout ?? 5000)
					return options.hooks?.error?.(incoming)
				},
				success: (payload) => {
					if (options?.reset !== false) {
						reset()
					}
					successful.value = true
					recentlySuccessful.value = true
					timeoutIds.recentlySuccessful = setTimeout(() => recentlySuccessful.value = false, options?.timeout ?? 5000)
					return options.hooks?.success?.(payload)
				},
				after: (context) => {
					processing.value = false
					return options.hooks?.after?.(context)
				},
			},
		})
	}

	/**
	 * Clears all errors.
	 */
	function clearErrors() {
		errors.value = {}
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
		initial,
		fields,
		loaded,
		submit,
		abort,
		setErrors,
		clearErrors,
		hasErrors: computed(() => Object.values(errors.value).length > 0),
		isDirty: readonly(isDirty),
		errors: readonly(errors),
		processing: readonly(processing),
		successful: readonly(successful),
		failed: readonly(failed),
		recentlySuccessful: readonly(recentlySuccessful),
		recentlyFailed: readonly(recentlyFailed),
	})
}
