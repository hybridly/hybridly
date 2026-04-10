<script setup lang="ts">
import { useForm, useValidation, useValidationBag } from 'hybridly/vue'
import HybridDialog from '~/app/Components/hybrid-dialog.vue'
import JsonViewer from '~/app/Components/json-viewer.vue'

defineProps<{
	spellRiskOptions: Record<string, string>
}>()

const validation = useValidation()
const dialogValidation = useValidationBag('dialog_spell_discovery')

const dialogForm = useForm<App.KitchenSink.Forms.Validation.SpellDiscoveryRequest>({
	key: 'forms.validation.dialogSpellDiscovery',
	url: route('kitchen-sink.forms.validation.spell-discovery'),
	method: 'POST',
	errorBag: 'dialog_spell_discovery',
	fields: {
		mage_reference_id: '',
		spell_name: '',
		spell_risk_level: '',
		spell_explanation: '',
	},
	transform: (fields) => ({
		...fields,
		mage_reference_id: fields.mage_reference_id.trim().toUpperCase(),
		spell_name: fields.spell_name.trim(),
		spell_explanation: fields.spell_explanation.trim(),
	}),
})
</script>

<template>
	<hybrid-dialog>
		<template #title="{ close }">
			<div class="flex justify-between items-start gap-4 w-full">
				<div>
					<p class="font-medium text-highlighted text-sm">
						Dialog form validation
					</p>
					<p class="mt-1 text-muted text-sm">
						Submit this form from a modal and inspect its scoped validation bag.
					</p>
				</div>
				<u-button type="button" color="neutral" variant="ghost" icon="lucide:x" @click="close()" />
			</div>
		</template>

		<form class="flex flex-col gap-4" @submit.prevent="dialogForm.submit()">
			<div class="gap-4 grid grid-cols-1 sm:grid-cols-2">
				<u-form-field label="Mage reference ID" name="mage_reference_id" :error="dialogForm.errors.mage_reference_id">
					<u-input v-model="dialogForm.fields.mage_reference_id" placeholder="MG-12A9-9981" class="w-full" />
				</u-form-field>

				<u-form-field label="Name" name="spell_name" :error="dialogForm.errors.spell_name">
					<u-input v-model="dialogForm.fields.spell_name" placeholder="Zoltraak" class="w-full" />
				</u-form-field>
			</div>

			<u-form-field label="Risk level" name="spell_risk_level" :error="dialogForm.errors.spell_risk_level">
				<u-select-menu
					v-model="dialogForm.fields.spell_risk_level"
					searchable
					value-key="value"
					label-key="label"
					placeholder="Select a risk level"
					class="w-full"
					:items="Object.entries(spellRiskOptions).map(([key, value]) => ({
						label: value,
						value: key,
					}))"
				/>
			</u-form-field>

			<u-form-field label="How the spell works" name="spell_explanation" :error="dialogForm.errors.spell_explanation">
				<u-textarea
					v-model="dialogForm.fields.spell_explanation"
					placeholder="Explain the mechanics and observed effects"
					class="w-full"
				/>
			</u-form-field>

			<div class="flex flex-wrap justify-end items-center gap-2">
				<u-button type="button" color="neutral" variant="subtle" label="Reset" @click="dialogForm.reset()" />
				<u-button
					type="button"
					color="neutral"
					variant="subtle"
					label="Clear errors"
					@click="dialogForm.clearErrors()"
				/>
				<u-button
					type="button"
					color="primary"
					variant="subtle"
					label="Submit and don't reset"
					:loading="dialogForm.processing"
					@click="dialogForm.submitWith({ reset: false })"
				/>
				<u-button type="submit" color="primary" variant="subtle" label="Submit" :loading="dialogForm.processing" />
			</div>
		</form>
	</hybrid-dialog>
</template>
