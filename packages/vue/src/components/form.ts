import type { SearchableObject } from '@clickbar/dot-diver'
import type { HybridRequestOptions, Method } from '@hybridly/core'
import { merge } from '@hybridly/utils'
import { get, set } from 'es-toolkit/compat'
import type { ComponentObjectPropsOptions, PropType } from 'vue'
import { defineComponent, h, nextTick, ref, type SlotsType } from 'vue'
import { type FormReturn, useForm } from '../composables/form'

type DefaultFormFields = Record<string, any>
type FormFields = DefaultFormFields
type FormControl = HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement
type SubmitterElement = HTMLButtonElement | HTMLInputElement
type FormInternalSubmitOptions<T extends SearchableObject = DefaultFormFields> = NonNullable<Parameters<FormReturn<T>['submit']>[0]>
type FormFieldBehavior = boolean | string[]

type FormRequestOptions = Omit<HybridRequestOptions, 'url' | 'data' | 'method' | 'errorBag' | 'progress'>

export type FormSubmitOptions<T extends SearchableObject = DefaultFormFields> = FormInternalSubmitOptions<T>

export type FormSlotProps<T extends SearchableObject = DefaultFormFields> = Omit<FormReturn<T>, 'submit' | 'reset' | 'resetFields' | 'errors'> & {
	errors: Record<string, any>
	getError: (key: string) => any
	submit: (optionsOverrides?: FormSubmitOptions<T>) => Promise<any>
	reset: () => void
	resetFields: (...keys: string[]) => void
}

export interface FormProps {
	action?: string
	method?: Method | Lowercase<Method>
	options?: FormRequestOptions
	errorBag?: string
	showProgress?: boolean
	disableWhileProcessing?: boolean
	resetOnSuccess?: FormFieldBehavior
	resetOnError?: FormFieldBehavior
	setDefaultOnSuccess?: FormFieldBehavior
}

function isSubmitterElement(value: unknown): value is SubmitterElement {
	if (value instanceof HTMLButtonElement) {
		return true
	}

	if (value instanceof HTMLInputElement) {
		return value.type === 'submit' || value.type === 'image'
	}

	return false
}

function normalizeFieldName(name: string): { path: string; appendToArray: boolean } {
	const appendToArray = name.endsWith('[]')
	const path = (appendToArray ? name.slice(0, -2) : name)
		.replace(/\[([^\]]+)\]/g, '.$1')
		.replace(/^\./, '')

	return {
		path,
		appendToArray,
	}
}

function appendFieldValue(target: FormFields, path: string, value: FormDataEntryValue, forceArray: boolean) {
	const existing = get(target, path)

	if (existing === undefined) {
		set(target, path, forceArray ? [value] : value)
		return
	}

	if (Array.isArray(existing)) {
		existing.push(value)
		return
	}

	set(target, path, [existing, value])
}

function isFormControl(control: Element): control is FormControl {
	return control instanceof HTMLInputElement
		|| control instanceof HTMLTextAreaElement
		|| control instanceof HTMLSelectElement
}

function getControlFieldInfo(control: Element): { control: FormControl; path: string; appendToArray: boolean } | undefined {
	if (!isFormControl(control) || !control.name) {
		return undefined
	}

	const { path, appendToArray } = normalizeFieldName(control.name)
	if (!path.length) {
		return undefined
	}

	return {
		control,
		path,
		appendToArray,
	}
}

function setCurrentValuesAsDefaults(form: HTMLFormElement, keys?: Set<string>) {
	for (const element of Array.from(form.elements)) {
		const field = getControlFieldInfo(element)

		if (keys && (!field || !keys.has(field.path))) {
			continue
		}

		if (element instanceof HTMLInputElement) {
			if (element.type === 'checkbox' || element.type === 'radio') {
				element.defaultChecked = element.checked
				continue
			}

			if (element.type === 'file') {
				continue
			}

			element.defaultValue = element.value
			continue
		}

		if (element instanceof HTMLTextAreaElement) {
			element.defaultValue = element.value
			continue
		}

		if (element instanceof HTMLSelectElement) {
			for (const option of Array.from(element.options)) {
				option.defaultSelected = option.selected
			}
		}
	}
}

function resetControlToDefault(control: Element) {
	if (control instanceof HTMLInputElement) {
		if (control.type === 'checkbox' || control.type === 'radio') {
			control.checked = control.defaultChecked
			return
		}

		if (control.type === 'file') {
			control.value = ''
			return
		}

		control.value = control.defaultValue
		return
	}

	if (control instanceof HTMLTextAreaElement) {
		control.value = control.defaultValue
		return
	}

	if (control instanceof HTMLSelectElement) {
		for (const option of Array.from(control.options)) {
			option.selected = option.defaultSelected
		}
	}
}

function applyFieldValueToControl(control: Element, fieldValue: unknown) {
	if (control instanceof HTMLInputElement) {
		if (control.type === 'file') {
			return
		}

		if (control.type === 'checkbox') {
			if (Array.isArray(fieldValue)) {
				control.checked = fieldValue.map(String).includes(control.value)
				return
			}

			if (typeof fieldValue === 'boolean') {
				control.checked = fieldValue
				return
			}

			control.checked = String(fieldValue ?? '') === control.value
			return
		}

		if (control.type === 'radio') {
			control.checked = String(fieldValue ?? '') === control.value
			return
		}

		control.value = fieldValue == null ? '' : String(fieldValue)
		return
	}

	if (control instanceof HTMLTextAreaElement) {
		control.value = fieldValue == null ? '' : String(fieldValue)
		return
	}

	if (control instanceof HTMLSelectElement) {
		if (control.multiple && Array.isArray(fieldValue)) {
			const values = new Set(fieldValue.map((value) => String(value)))

			for (const option of Array.from(control.options)) {
				option.selected = values.has(option.value)
			}

			return
		}

		control.value = fieldValue == null ? '' : String(fieldValue)
	}
}

function syncControlsFromFields(form: HTMLFormElement, fields: FormFields) {
	const pathIndexes = new Map<string, number>()

	for (const control of Array.from(form.elements)) {
		const field = getControlFieldInfo(control)
		if (!field) {
			continue
		}

		const value = get(fields, field.path)

		if (field.appendToArray && Array.isArray(value)) {
			const currentIndex = pathIndexes.get(field.path) ?? 0
			applyFieldValueToControl(control, value[currentIndex])
			pathIndexes.set(field.path, currentIndex + 1)
			continue
		}

		applyFieldValueToControl(control, value)
	}
}

export const Form = defineComponent({
	name: 'Form',
	inheritAttrs: false,
	props: {
		action: {
			type: String,
			required: false,
			default: undefined,
		},
		method: {
			type: String as PropType<NonNullable<FormProps['method']>>,
			default: 'POST',
		},
		options: {
			type: Object as PropType<FormRequestOptions>,
			default: () => ({}),
		},
		errorBag: {
			type: String,
			required: false,
			default: undefined,
		},
		showProgress: {
			type: Boolean,
			required: false,
			default: undefined,
		},
		disableWhileProcessing: {
			type: Boolean,
			default: false,
		},
		resetOnSuccess: {
			type: [Boolean, Array] as PropType<FormFieldBehavior>,
			default: true,
		},
		resetOnError: {
			type: [Boolean, Array] as PropType<FormFieldBehavior>,
			default: false,
		},
		setDefaultOnSuccess: {
			type: [Boolean, Array] as PropType<FormFieldBehavior>,
			default: false,
		},
	} satisfies ComponentObjectPropsOptions<FormProps>,
	slots: Object as SlotsType<{
		default: FormSlotProps
	}>,
	setup(props, { attrs, slots }) {
		const element = ref<HTMLFormElement>()

		const form = useForm<FormFields>({
			fields: {},
			url: () => props.action ?? element.value?.action ?? window.location.href,
			method: props.method,
			errorBag: props.errorBag,
			progress: props.showProgress,
			resetOnSuccess: props.resetOnSuccess,
			resetOnError: props.resetOnError,
			setDefaultOnSuccess: props.setDefaultOnSuccess,
		})

		function collectFormFields(submitter?: SubmitterElement): FormFields {
			if (!element.value) {
				return {}
			}

			const formData = submitter
				? new FormData(element.value, submitter)
				: new FormData(element.value)

			const fields: FormFields = {}

			for (const [name, value] of formData.entries()) {
				const { path, appendToArray } = normalizeFieldName(name)

				if (!path.length) {
					continue
				}

				appendFieldValue(fields, path, value, appendToArray)
			}

			return fields
		}

		function collectDefaultFormFields(): FormFields {
			if (!element.value) {
				return {}
			}

			const fields: FormFields = {}

			for (const control of Array.from(element.value.elements)) {
				const field = getControlFieldInfo(control)

				if (!field) {
					continue
				}

				if (control instanceof HTMLInputElement) {
					if (control.type === 'file') {
						continue
					}

					if (control.type === 'checkbox' || control.type === 'radio') {
						if (control.defaultChecked) {
							appendFieldValue(fields, field.path, control.value, field.appendToArray)
						}

						continue
					}

					appendFieldValue(fields, field.path, control.defaultValue, field.appendToArray)
					continue
				}

				if (control instanceof HTMLTextAreaElement) {
					appendFieldValue(fields, field.path, control.defaultValue, field.appendToArray)
					continue
				}

				if (control instanceof HTMLSelectElement) {
					if (control.multiple) {
						for (const option of Array.from(control.options)) {
							if (option.defaultSelected) {
								appendFieldValue(fields, field.path, option.value, field.appendToArray)
							}
						}

						continue
					}

					const defaultOption = Array.from(control.options).find((option) => option.defaultSelected)
						?? control.options.item(0)

					if (defaultOption) {
						appendFieldValue(fields, field.path, defaultOption.value, field.appendToArray)
					}
				}
			}

			return fields
		}

		function syncDefaultsFromNativeControls() {
			if (!element.value) {
				return
			}

			const defaultFields = collectDefaultFormFields()
			const nextDefaults: Record<string, unknown> = {}

			for (const control of Array.from(element.value.elements)) {
				const field = getControlFieldInfo(control)

				if (!field) {
					continue
				}

				nextDefaults[field.path] = get(defaultFields, field.path)
			}

			form.setDefault(nextDefaults as Partial<FormFields>)
		}

		function syncFields(fields: FormFields) {
			const target = form.fields as Record<string, unknown>

			Object.keys(target).forEach((key) => {
				delete target[key]
			})

			Object.entries(fields).forEach(([key, value]) => {
				target[key] = value
			})
		}

		function resetFields(...keys: string[]) {
			if (!element.value) {
				return
			}

			if (keys.length === 0) {
				element.value.reset()
			} else {
				const keySet = new Set(keys)

				for (const control of Array.from(element.value.elements)) {
					const field = getControlFieldInfo(control)

					if (!field || !keySet.has(field.path)) {
						continue
					}

					resetControlToDefault(control)
				}
			}

			syncFields(collectFormFields())
		}

		function reset() {
			form.resetSubmissionState()
			form.clearErrors()
			resetFields()
		}

		function submit(options?: FormSubmitOptions, submitter?: SubmitterElement) {
			const fields = collectFormFields(submitter)
			syncFields(fields)
			syncDefaultsFromNativeControls()

			const resolvedOptions = merge<FormSubmitOptions<FormFields>>(
				{
					errorBag: props.errorBag,
					progress: props.showProgress,
					resetOnSuccess: props.resetOnSuccess,
					resetOnError: props.resetOnError,
					setDefaultOnSuccess: props.setDefaultOnSuccess,
				},
				{ ...props.options, ...options },
				{ mergePlainObjects: true },
			)

			const { hooks, ...rest } = resolvedOptions as FormSubmitOptions<FormFields>

			const submitOptions: FormInternalSubmitOptions<FormFields> = {
				...(rest as FormInternalSubmitOptions<FormFields>),
				hooks: {
					...hooks,
					'validation-error': (incoming, request, context) => {
						nextTick(() => {
							if (!element.value) {
								return
							}

							syncControlsFromFields(element.value, form.fields as FormFields)
						})

						return hooks?.['validation-error']?.(incoming, request, context)
					},
					success: (payload, request, response, context) => {
						if (element.value && rest.setDefaultOnSuccess !== false) {
							const selectedKeys = Array.isArray(rest.setDefaultOnSuccess)
								? new Set(rest.setDefaultOnSuccess)
								: undefined

							setCurrentValuesAsDefaults(element.value, selectedKeys)
							syncDefaultsFromNativeControls()
						}

						nextTick(() => {
							if (!element.value) {
								return
							}

							syncControlsFromFields(element.value, form.fields as FormFields)
						})

						return hooks?.success?.(payload, request, response, context)
					},
				},
			}

			return form.submit(submitOptions)
		}

		function onSubmit(event: SubmitEvent) {
			event.preventDefault()

			const submitter = isSubmitterElement(event.submitter)
				? event.submitter
				: undefined

			return submit(undefined, submitter)
		}

		return () =>
			h(
				'form',
				{
					...attrs,
					ref: element,
					action: props.action,
					method: props.method,
					inert: props.disableWhileProcessing && form.processing ? '' : undefined,
					onSubmit,
				},
				slots.default?.({
					...form,
					errors: form.errors as Record<string, any>,
					getError: (key: string) => get(form.errors as Record<string, unknown>, key) as any,
					submit,
					resetFields,
					reset,
				}),
			)
	},
})
