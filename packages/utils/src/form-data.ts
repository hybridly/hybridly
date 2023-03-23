export type RequestData = Record<string, FormDataConvertible> | FormDataConvertible | FormData
export type FormDataConvertible =
	| { [key: string]: FormDataConvertible }
	| Array<FormDataConvertible>
	| Set<FormDataConvertible>
	| Blob
	| File
	| FormDataEntryValue
	| Date
	| boolean
	| number
	| null
	| undefined
	| string

/**
 * Checks if the given object has a file.
 */
export function hasFiles(data?: RequestData): boolean {
	if (!data) {
		return false
	}

	return data instanceof File
		|| data instanceof Blob
		|| (data instanceof FileList && data.length > 0)
		|| (data instanceof FormData && Array.from(data.values()).some((value) => hasFiles(value)))
		|| (typeof data === 'object' && data !== null && Object.values(data).some((value) => hasFiles(value)))
}

/**
 * Converts an object literal to a `FormData` object.
 */
export function objectToFormData(
	source?: RequestData,
	form?: FormData,
	parentKey?: string,
): FormData {
	source ??= {}
	form ??= new FormData()

	if (typeof source !== 'object' || (source instanceof Set) || Array.isArray(source) || (source instanceof Blob) || (source instanceof Date) || (source instanceof FormData)) {
		throw new TypeError('Source must be an object literal to be converted to a FormData object.')
	}

	for (const key in source) {
		if (Object.prototype.hasOwnProperty.call(source, key)) {
			append(form, composeKey(key, parentKey), source[key])
		}
	}

	return form
}

function composeKey(key: string, parentKey?: string): string {
	return parentKey ? `${parentKey}[${key}]` : key
}

function append(form: FormData, key: string, value: FormDataConvertible): void {
	if (Array.isArray(value) || value instanceof Set) {
		const valueAsArray = value instanceof Set
			? [...value]
			: value

		// FormData cannot have empty arrays, so we use an empty string instead,
		// which back-end should interpret as a null value.
		// See: https://github.com/inertiajs/inertia/pull/876
		if (!valueAsArray.length) {
			return form.append(key, '')
		}

		return Array.from(valueAsArray.keys()).forEach((index) => append(form, composeKey(index.toString(), key), valueAsArray[index]))
	} else if (value instanceof Date) {
		return form.append(key, value.toISOString())
	} else if (value instanceof File) {
		return form.append(key, value, value.name)
	} else if (value instanceof Blob) {
		return form.append(key, value)
	} else if (typeof value === 'boolean') {
		return form.append(key, value ? '1' : '0')
	} else if (typeof value === 'string') {
		return form.append(key, value)
	} else if (typeof value === 'number') {
		return form.append(key, `${value}`)
	} else if (value === null || value === undefined) {
		return form.append(key, '')
	}

	objectToFormData(value, form, key)
}
