<script setup lang="ts">
import Card from '~/app/Components/card.vue'
</script>

<template>
	<div class="flex flex-col justify-center items-center gap-8 size-full grow">
		<!-- buttons -->
		<card
			title="Named transitions"
			:external-references="['https://web.dev/learn/css/view-transitions-spas#transitioning_specific_elements']"
			class="max-w-3xl"
		>
			<template #description>
				Specifying a name to the <code class="text-toned">viewTransition</code> property allows for different
				transitions based on the provided name.
			</template>
			<div class="flex justify-center gap-4">
				<u-button
					icon="lucide:arrow-left"
					label="Use 'backwards'"
					@click="router.get(route('kitchen-sink.navigation.view-transitions.left'), { viewTransition: 'backwards' })"
				/>
				<u-button
					label="Use 'true'"
					@click="router.get(route('kitchen-sink.navigation.view-transitions.index'), { viewTransition: true })"
				/>
				<u-button
					label="Use 'false'"
					@click="router.get(route('kitchen-sink.navigation.view-transitions.index'), { viewTransition: false })"
				/>
				<u-button
					trailing-icon="lucide:arrow-right"
					label="Use 'forwards'"
					@click="router.get(route('kitchen-sink.navigation.view-transitions.right'), { viewTransition: 'forwards' })"
				/>
			</div>
		</card>
		<slot />
	</div>
</template>

<style>
:root {
	--vt-duration: 300ms;
	--vt-translate-x: 200px;
}

html::view-transition-old(--layout),
html::view-transition-new(--layout) {
	will-change: transform;
}

html:active-view-transition-type(forwards) {
	&::view-transition-old(--layout) {
		animation: scale-out-forwards var(--vt-duration) ease-out both;
	}
	&::view-transition-new(--layout) {
		animation: scale-in-forwards calc(var(--vt-duration) + 20ms) ease-out both;
	}
}

html:active-view-transition-type(backwards) {
	&::view-transition-old(--layout) {
		animation: scale-out-backwards var(--vt-duration) ease-out both;
	}
	&::view-transition-new(--layout) {
		animation: scale-in-backwards calc(var(--vt-duration) + 20ms) ease-out both;
	}
}

@keyframes scale-out-forwards {
	from {
		transform: translateX(0px);
		opacity: 1;
	}
	to {
		transform: translateX(calc(0px - var(--vt-translate-x)));
		opacity: 0;
	}
}

@keyframes scale-in-forwards {
	from {
		opacity: 0;
	}
	to {
		transform: translateX(0px);
		opacity: 1;
	}
}

@keyframes scale-out-backwards {
	from {
		transform: translateX(0px);
		opacity: 1;
	}
	to {
		transform: translateX(calc(0px + var(--vt-translate-x)));
		opacity: 0;
	}
}

@keyframes scale-in-backwards {
	from {
		opacity: 0;
	}
	to {
		transform: translateX(0px);
		opacity: 1;
	}
}
</style>
