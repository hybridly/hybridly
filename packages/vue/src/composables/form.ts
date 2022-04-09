import isEqual from 'lodash.isequal'
import clone from 'lodash.clone'
import type { VisitOptions } from '@sleightful/core'
import { computed, reactive, readonly, ref, toRaw, watch } from 'vue'
import { router } from '../router'

type Fields = Record<string, any>

interface FormOptions<T extends Fields> extends Omit<VisitOptions, 'data'> {
	url: string
	fields: T
	key?: string | false
	timeout?: number
	transform?: (fields: T) => Fields
}

export function useForm<T extends Fields = Fields>(options: FormOptions<T>) {
	let recentlySuccessfulTimeoutId: any
	const shouldRemember = options?.key !== false
	const historyKey = options?.key as string ?? 'form:default'
	const historyFields = shouldRemember ? router.history.get(historyKey) as T : undefined

	/** Initial fields. */
	const initial = readonly(clone(options.fields))
	/** Current fields. */
	const fields = reactive(clone(historyFields ?? options.fields))
	/** Validation errors for each field. */
	const errors = ref<Record<string, string>>({})
	/** Whether the form is dirty. */
	const isDirty = ref(false)
	/** Whether the submission was recently successful. */
	const recentlySuccessful = ref(false)
	/** Whether the submission is successful. */
	const successful = ref(false)
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
		return router.visit({
			method: options.method ?? 'POST',
			...options,
			...optionsOverrides,
			data: options.transform?.(fields) ?? fields,
			events: {
				before: (visit) => {
					successful.value = false
					recentlySuccessful.value = false
					clearTimeout(recentlySuccessfulTimeoutId)
					clearErrors()
					return options.events?.before?.(visit)
				},
				start: (context) => {
					processing.value = true
					return options.events?.start?.(context)
				},
				error: (incoming) => {
					clearErrors()
					setErrors(incoming)
					return options.events?.error?.(incoming)
				},
				success: (payload) => {
					reset()
					successful.value = true
					recentlySuccessful.value = true
					recentlySuccessfulTimeoutId = setTimeout(() => recentlySuccessful.value = false, options?.timeout ?? 5000)
					return options.events?.success?.(payload)
				},
				after: (context) => {
					processing.value = false
					return options.events?.after?.(context)
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
		errors.value = Object.assign({}, toRaw(incoming))
	}

	/**
	 * Aborts the submission.
	 */
	function abort() {
		router.abort()
	}

	watch(fields, () => {
		isDirty.value = !isEqual(toRaw(initial), toRaw(fields))

		if (shouldRemember) {
			router.history.remember(historyKey, toRaw(fields))
		}
	}, { deep: true, immediate: true })

	return {
		reset,
		initial,
		fields,
		submit,
		hasErrors: computed(() => Object.values(errors.value).length > 0),
		setErrors,
		clearErrors,
		abort,
		isDirty: readonly(isDirty),
		errors: readonly(errors),
		processing: readonly(processing),
		successful: readonly(successful),
		recentlySuccessful: readonly(recentlySuccessful),
	}
}
