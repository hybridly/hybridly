<script setup lang="ts">
import laravel from './laravel.png'
import vue from './vue.png'
</script>

<template>
	<section class="mx-auto w-full max-w-screen-xl px-5 pt-20">
		<div class="flex items-center justify-center gap-3 sm:gap-10">
			<div class="bg-[currentColor] h-px flex-1" />
			<div class="text-3xl font-extrabold sm:text-4xl">
				Let's see some code
			</div>
			<div class="bg-[currentColor] h-px flex-1" />
		</div>
		<div class="pt-14">
			<div class="grid grid-cols-8 gap-10">
				<div class="col-span-full lg:col-span-2">
					<div class="text-3xl font-bold text-[#FF2D20]">
						Laravel
					</div>
					<div class="text-4xl font-black">
						Backend
					</div>
				</div>
				<div class="border-b-dashed relative top-10 col-span-4 hidden h-1 opacity-50 lg:block">
					<div
						class="i-fluent-chevron-right-16-filled absolute top-[-0.60rem]
                        -right-3 h-6 w-6
                        "
					/>
				</div>
				<div class="col-span-full lg:col-span-2 lg:text-right">
					<div class="text-3xl font-bold text-[#33A06F]">
						Vue
					</div>
					<div class="text-4xl font-black">
						Frontend
					</div>
				</div>
				<div class="col-span-full row-start-2 justify-self-center lg:col-span-4 lg:row-start-auto lg:justify-self-auto">
					<img :src="laravel" class="w-[30rem]" alt="Hybridly Laravel Code Example" />
				</div>
				<div class="col-span-full justify-self-center lg:col-span-4 lg:justify-self-end">
					<img :src="vue" class="w-96" alt="Hybridly Vue Code Example" />
				</div>
			</div>
			<div class="grid justify-center pt-20">
				<div class="text-4xl font-bold">
					Looks like Magic!
				</div>
				<div class="flex items-center gap-3 pt-3">
					<div class="bg-[currentColor] h-0.5 w-32 rounded-full opacity-20" />
					<div class="text-3xl font-medium text-violet-500">
						Doesn’t it ?
					</div>
				</div>
				<div class="max-w-[30rem] pt-5 text-xl leading-relaxed">
					You can send data to <span class="font-extrabold">Vue</span>
					by simply returning the <span class="font-extrabold">hybridly</span>
					function in your <span class="font-extrabold">Laravel</span> controller.
					<br />
					You just have to tell it the name of your Vue file, and the data you want to pass.
					<br />
					From there you can access the passed data in your Vue template!
					<br />
					<br />
					How cool is that ?
				</div>
			</div>
		</div>
	</section>
</template>
