<script setup lang="ts">
import type { NavigationMenuItem } from '@nuxt/ui'

const open = ref(true)
const navigation = useProperty('navigation')
const breadcrumbs = useProperty('breadcrumbs')
const items = computed<NavigationMenuItem[]>(() =>
	navigation.value.map((item) => ({
		label: item.label,
		icon: item.icon,
		href: item.href,
		active: item.active,
		external: item.external,
		children: item.children,
		slot: item.slot,
		defaultOpen: true,
	}))
)
const breadcrumb_items = computed(() =>
	breadcrumbs.value.map((breadcrumb) => ({
		label: breadcrumb.label,
		href: breadcrumb.href,
	}))
)
</script>

<template>
	<u-app>
		<u-sidebar
			v-model:open="open"
			collapsible="icon"
			variant="inset"
			side="left"
			:ui="{ container: 'h-full' }"
			class="[--sidebar-width:20rem]"
		>
			<template #header>
				<u-icon name="i-logos-nuxt-icon" class="size-8" />
			</template>
			<u-navigation-menu :items orientation="vertical" variant="link" :ui="{ link: 'p-1.5 overflow-hidden' }" />
		</u-sidebar>
		<div
			class="flex flex-col flex-1 bg-default peer-data-[variant=inset]:shadow-sm peer-data-[variant=inset]:m-4 lg:peer-data-[variant=floating]:my-4 lg:peer-data-[variant=inset]:not-peer-data-[collapsible=offcanvas]:ms-0 peer-data-[variant=inset]:rounded-xl peer-data-[variant=inset]:ring peer-data-[variant=inset]:ring-default overflow-hidden"
			style="view-transition-name: --layout"
		>
			<!-- header -->
			<div id="header" class="h-(--ui-header-height) shrink-0 flex items-center px-4 border-b border-default gap-4">
				<!-- toggle panel -->
				<u-button
					icon="lucide:panel-left"
					color="neutral"
					variant="ghost"
					aria-label="Toggle sidebar"
					@click="open = !open"
				/>
				<!-- title -->
				<div class="flex items-center gap-4">
					<u-breadcrumb v-if="breadcrumb_items.length > 0" :items="breadcrumb_items" class="text-sm" />
				</div>
				<!-- actions -->
				<div id="header-actions" class="flex items-center gap-4 ml-auto" />
			</div>
			<!-- content -->
			<div class="flex flex-col flex-1 p-4 overflow-auto grow">
				<slot />
			</div>
		</div>
	</u-app>
</template>
