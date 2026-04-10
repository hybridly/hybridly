import 'hybridly'

declare module 'hybridly' {
	export interface GlobalHybridlyProperties {
		navigation: App.Navigation.SharedNavigationItem[]
		breadcrumbs: App.Navigation.SharedBreadcrumb[]
	}
}

export {}
