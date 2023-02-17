export interface Authorizable<Authorizations extends Record<string, boolean>> {
	authorization: Authorizations
}

/**
 * Checks whether the given data has the authorization for the given action.
 * If the data object has no authorization definition corresponding to the given action, this method will return `false`.
 */
function can<
    Authorizations extends Record<string, boolean>,
    Data extends Authorizable<Authorizations>,
    Action extends keyof Data['authorization']
>(action: Action, resource: Data) {
    if (resource === undefined) {
        return useProperty('security.user').value?.authorization?.[action] ?? false
    }

    return resource.authorization?.[action] ?? false
}

function cannot<
    Authorizations extends Record<string, boolean>,
    Data extends Authorizable<Authorizations>,
    Action extends keyof Data['authorization']
>(action: Action, resource: Data) {
    return ! can(action, resource)
}
