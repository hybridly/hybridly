type Property = null | string | number | boolean | Property[] | { [name: string]: Property }
type Properties = Record<string | number, Property>

interface GlobalMonolikitProperties {
	[key: string | number]: Property
}
