/* eslint-disable unused-imports/no-unused-vars */
/* eslint-disable @typescript-eslint/no-unused-vars */

export type FormDataEntryValue = File | string

export class FormData {
	append(name: string, value: string | Blob, fileName?: string): void {}
	delete(name: string): void {}
	get(name: string): FormDataEntryValue | null {
		return null
	}

	getAll(name: string): FormDataEntryValue[] {
		return []
	}

	has(name: string): boolean {
		return false
	}

	set(name: string, value: string | Blob, fileName?: string): void {}
	forEach(callbackfn: (value: FormDataEntryValue, key: string, parent: FormData) => void, thisArg?: any): void {}
}
