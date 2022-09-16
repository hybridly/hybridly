export interface Authorizable<Authorizations extends Record<string, boolean>> {
	authorization: Authorizations
}

/**
 * Checks whether the given data has the authorization for the given action.
 * If the data object has no authorization definition corresponding to the given action, this method will return `false`.
 */
export function can<
	Authorizations extends Record<string, boolean>,
	Data extends Authorizable<Authorizations>,
	Action extends keyof Data['authorization']
>(resource: Data, action: Action) {
	return resource.authorization?.[action] ?? false
}
