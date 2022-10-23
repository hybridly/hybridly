<script setup lang="ts">
import FeaturesSection from './features/features-section.vue'
import MiniFeaturesSection from './mini-features/mini-features-section.vue'
</script>

<template>
	<ClientOnly>
		<section class="mt-20 flex h-full flex-col lg:mt-40">
			<!-- Hero -->
			<div class="mx-auto flex w-full max-w-5xl flex-col items-center justify-between gap-x-40 px-6 lg:flex-row lg:items-start lg:px-0">
				<!-- Left block -->
				<section class="max-w-2xl grow">
					<!-- Title -->
					<h1 class="title font-title title-bg bg-clip-text text-center text-5xl font-medium uppercase text-transparent lg:text-left lg:text-8xl">
						Hybridly
					</h1>
					<!-- Subtitle -->
					<p class="mt-2 text-center text-2xl font-bold lg:text-left lg:text-3xl">
						Server-driven, client-rendered applications.
					</p>
					<!-- Description -->
					<p class="mt-4 max-w-md text-center text-lg font-medium opacity-60 lg:text-left lg:text-xl">
						Build single-page applications the classic way. No API, no route duplication, no front-end state. Fully server-driven.
					</p>
					<!-- Actions -->
					<div class="mt-10 flex flex-wrap justify-center gap-4 lg:justify-start">
						<a
							href="/guide/"
							class="group flex w-40 items-center justify-center gap-2 rounded-full bg-[#FFEAEF] py-3 text-[#DB7DA8]
							transition hover:bg-[#FFDBE3] hover:text-[#D6689B]
							dark:bg-violet-500 dark:text-white dark:hover:bg-violet-600 dark:hover:text-white
							"
						>
							<div class="pl-1.5 font-semibold">Get started</div>
							<div class="relative">
								<div
									class="
									bg-[currentColor] absolute top-1/2 left-1 h-0.5 w-2.5 -translate-y-1/2 rounded-full
									opacity-0 transition group-hover:translate-x-0.5 group-hover:opacity-100
									"
								/>
								<div
									class="i-fluent-chevron-right-16-filled h-5 w-5
									transition group-hover:translate-x-1
									"
								/>
							</div>
						</a>
						<a
							class="bg-[var(--vp-c-bg-soft)] hover:bg-[var(--vp-c-bg-mute)] group
							text-[var(--vp-c-text-1)] flex items-center rounded-full px-5 py-2 font-medium transition"
							href="/guide/demonstration"
						>
							<span class="transition group-hover:translate-x-8">Demo</span>
							<span class="transition group-hover:opacity-0">nstration</span>
						</a>
						<a
							class="bg-[var(--vp-c-bg-soft)] hover:bg-[var(--vp-c-bg-mute)] group text-[var(--vp-c-text-1)]
							relative flex w-44 items-center justify-center rounded-full py-2 font-medium transition"
							href="https://github.com/hybridly/hybridly"
						>
							<span class="transition group-hover:opacity-0">See on GitHub</span>
							<span
								class="absolute top-1/2 right-1/2 flex -translate-y-1/2 translate-x-1/3 items-center gap-2
								opacity-0 transition group-hover:translate-x-1/2 group-hover:opacity-100
								"
							>
								<div class="i-ph-star-fill h-5 w-5 text-amber-400" />
								<div class="">Star us!</div>
							</span>
						</a>
					</div>
				</section>

				<!-- Logo block -->
				<div class="lg:h-55 lg:w-55 h-30 w-30 relative order-first mb-20 flex shrink-0 items-center justify-center lg:order-last lg:mt-20 lg:mr-20">
					<img class="absolute z-[1] h-full w-full" src="/logo.svg" />
					<div class="image-bg absolute -inset-5 rotate-45 opacity-40 dark:opacity-70" />
				</div>
			</div>

			<MiniFeaturesSection />
			<FeaturesSection />
		</section>
	</ClientOnly>
</template>

<style scoped>
.title-bg {
	animation: title-gradient 20s ease infinite;
	background-size: 400% 400%;
  background-image: linear-gradient(
		-45deg,
		var(--vp-logo-shadow-1),
		var(--vp-logo-shadow-2),
		var(--vp-logo-shadow-3),
		var(--vp-logo-shadow-4),
		var(--vp-logo-shadow-2),
		var(--vp-logo-shadow-4),
		var(--vp-logo-shadow-3),
		var(--vp-logo-shadow-2)
	);
}

@keyframes title-gradient {
	0% {
		background-position: 0% 50%;
	}
	50% {
		background-position: 100% 50%;
	}
	100% {
		background-position: 0% 50%;
	}
}

.image-bg {
  filter: blur(40px);
	animation: image-gradient 10s ease infinite;
	background-size: 400% 400%;
  background-image: linear-gradient(
		-45deg,
		var(--vp-logo-shadow-1),
		var(--vp-logo-shadow-2),
		var(--vp-logo-shadow-3),
		var(--vp-logo-shadow-4),
		var(--vp-logo-shadow-2),
		var(--vp-logo-shadow-4),
		var(--vp-logo-shadow-3),
		var(--vp-logo-shadow-2)
	);
}

@keyframes image-gradient {
	0% {
		background-position: 0% 50%;
		border-radius: 75px;
		transform: rotate(0deg);
	}
	50% {
		background-position: 100% 50%;
		border-radius: 150px;
		transform: rotate(100deg);
	}
	100% {
		background-position: 0% 50%;
		border-radius: 75px;
		transform: rotate(00deg);
	}
}

.title::selection {
	background-color: rgba(244, 114, 182, 0.2);
	color: white;
}
</style>
