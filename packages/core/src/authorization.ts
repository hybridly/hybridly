export interface Authorizable {
	authorization: {
		[action: string]: boolean
	}
}

/**
 * Checks whether the given data has the authorization for the given action.
 * If the data object has no authorization definition corresponding to the given action, this method will return `false`.
 */
export function can<Data extends Authorizable>(resource: Data, action: keyof Data['authorization']): boolean {
	return resource.authorization?.[action as any] ?? false
}
