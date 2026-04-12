<script setup lang="ts">
import { Form } from 'hybridly/vue'
import { reactive } from 'vue'
import Card from '~/app/Components/card.vue'
import InfoText from '~/app/Components/info-text.vue'
import JsonViewer from '~/app/Components/json-viewer.vue'

defineProps<{
	spellRiskOptions: Record<string, string>
	lastSpellDiscoveryAt?: string
	form?: App.KitchenSink.Forms.FormComponent.SpellDiscoveryRequest
}>()

useHead({
	title: 'Form component',
})

const fieldBehaviorOptions = [
	{ label: 'Yes', value: 'yes' } as const,
	{ label: 'No', value: 'no' } as const,
	{ label: 'Spell name field', value: 'spell_name' } as const,
]

function resolveFieldBehavior(value: 'yes' | 'no' | 'spell_name'): boolean | string[] {
	if (value === 'yes') {
		return true
	}

	if (value === 'no') {
		return false
	}

	return [value]
}

const controls = reactive({
	disableWhileProcessing: true,
	setDefaultOnSuccess: 'no' as 'yes' | 'no' | 'spell_name',
	resetOnSuccess: 'yes' as 'yes' | 'no' | 'spell_name',
	resetOnError: 'no' as 'yes' | 'no' | 'spell_name',
})
</script>

<template layout>
	<div class="max-w-5xl">
		<Card title="Form component" description="A native-field form powered by the new <Form> component.">
			<info-text :date="{ timeStyle: 'medium' }" label="Last updated" :content="lastSpellDiscoveryAt" />

			<div class="gap-3 grid grid-cols-1 sm:grid-cols-4 mt-6">
				<u-checkbox v-model="controls.disableWhileProcessing" label="disable-while-processing" />

				<u-form-field label="set-default-on-success">
					<u-select
						v-model="controls.setDefaultOnSuccess"
						value-key="value"
						label-key="label"
						class="w-full"
						:items="fieldBehaviorOptions"
					/>
				</u-form-field>

				<u-form-field label="reset-on-success">
					<u-select
						v-model="controls.resetOnSuccess"
						value-key="value"
						label-key="label"
						class="w-full"
						:items="fieldBehaviorOptions"
					/>
				</u-form-field>

				<u-form-field label="reset-on-error">
					<u-select
						v-model="controls.resetOnError"
						value-key="value"
						label-key="label"
						class="w-full"
						:items="fieldBehaviorOptions"
					/>
				</u-form-field>
			</div>

			<Form
				#default="{ fields, errors, getError, processing, submit, clearErrors, reset }"
				class="flex flex-col gap-4 my-8"
				:action="route('kitchen-sink.forms.form-component.spell-discovery')"
				method="post"
				:disable-while-processing="controls.disableWhileProcessing"
				:set-default-on-success="resolveFieldBehavior(controls.setDefaultOnSuccess)"
				:reset-on-success="resolveFieldBehavior(controls.resetOnSuccess)"
				:reset-on-error="resolveFieldBehavior(controls.resetOnError)"
			>
				<div class="gap-4 grid grid-cols-3">
					<u-form-field label="Mage reference ID" name="mage_reference_id" :error="getError('mage_reference_id')">
						<u-input name="mage_reference_id" placeholder="MG-12A9-9981" class="w-full" />
					</u-form-field>

					<u-form-field label="Name" name="spell_name" :error="getError('spell_name')">
						<u-input name="spell_name" placeholder="Zoltraak" class="w-full" />
					</u-form-field>

					<u-form-field label="Risk level" name="spell_risk_level" :error="getError('spell_risk_level')">
						<u-select-menu
							placeholder="Select a risk level"
							searchable
							name="spell_risk_level"
							value-key="value"
							label-key="label"
							class="w-full"
							:items="Object.entries(spellRiskOptions).map(([key, value]) => ({
								label: value,
								value: key,
							}))"
						/>
					</u-form-field>
				</div>

				<u-form-field label="How the spell works" name="spell_explanation" :error="getError('spell_explanation')">
					<u-textarea
						name="spell_explanation"
						placeholder="Explain the mechanics and observed effects"
						class="w-full"
					/>
				</u-form-field>

				<div class="flex justify-end items-center gap-2">
					<u-button type="button" color="neutral" variant="subtle" label="Reset" @click="reset()" />
					<u-button type="button" color="neutral" variant="subtle" label="Clear errors" @click="clearErrors()" />
					<u-button
						type="button"
						color="primary"
						variant="subtle"
						label="Submit and don't reset form"
						:loading="processing"
						@click="submit({ resetOnSuccess: false })"
					/>
					<u-button type="submit" color="primary" variant="subtle" label="Submit" :loading="processing" />
				</div>

				<json-viewer
					:expanded="5"
					:data="{
						form,
						fields,
						validation: errors,
					}"
				/>
			</Form>
		</Card>
	</div>
</template>
