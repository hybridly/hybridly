import type { AddressInfo } from 'node:net'
import { fileURLToPath } from 'node:url'

export function dirname(): string {
	return fileURLToPath(new URL('.', import.meta.url))
}

export function isIpv6(address: AddressInfo): boolean {
	return address.family === 'IPv6'
	// In node >=18.0 <18.4 this was an integer value. This was changed in a minor version.
	// See: https://github.com/laravel/vite-plugin/issues/103
	// eslint-disable-next-line @typescript-eslint/ban-ts-comment
	// @ts-expect-error-next-line
		|| address.family === 6
}
