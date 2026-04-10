<script setup lang="ts">
import { useForm, useValidation, useValidationBag } from 'hybridly/vue'
import Card from '~/app/Components/card.vue'
import InfoText from '~/app/Components/info-text.vue'
import JsonViewer from '~/app/Components/json-viewer.vue'

const props = defineProps<{
	speciesOptions: Record<string, string>
	moralAlignmentOptions: Record<string, string>
	spellRiskOptions: Record<string, string>
	lastMageRegistrationAt: string | null
	lastSpellDiscoveryAt: string | null
}>()

useHead({
	title: 'Validation',
})

/*
|--------------------------------------------------------------------------
| Validation
|--------------------------------------------------------------------------
*/

const validation = useValidation()
const spellValidation = useValidationBag('spell_discovery')

/*
|--------------------------------------------------------------------------
| Mage registration
|--------------------------------------------------------------------------
*/

const mageRegistrationForm = useForm<App.KitchenSink.Forms.Validation.MageRegistrationRequest>({
	key: 'forms.validation.mageRegistration',
	url: route('kitchen-sink.forms.validation.mage-registration'),
	method: 'POST',
	fields: {
		full_name: '',
		date_of_birth: '',
		species: '',
		known_spells: [''],
		has_been_expelled: false,
		moral_alignment: '',
		comments: '',
	},
	transform: (fields) => ({
		...fields,
		known_spells: fields.known_spells
			.map((spell) => spell.trim())
			.filter((spell) => spell.length > 0),
		comments: (fields.comments ?? '').trim() || undefined,
	}),
})

/*
|--------------------------------------------------------------------------
| Spell discovery
|--------------------------------------------------------------------------
*/

const spellDiscoveryForm = useForm<App.KitchenSink.Forms.Validation.SpellDiscoveryRequest>({
	key: 'forms.validation.spellDiscovery',
	url: route('kitchen-sink.forms.validation.spell-discovery'),
	method: 'POST',
	errorBag: 'spell_discovery',
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

const knownSpellsError = computed(() => {
	const value = mageRegistrationForm.errors.known_spells as unknown

	if (!value) {
		return undefined
	}

	if (typeof value === 'string') {
		return value
	}

	if (Array.isArray(value)) {
		return value.find((entry) => typeof entry === 'string')
	}

	return undefined
})

function addKnownSpellField() {
	if (mageRegistrationForm.fields.known_spells.length >= 5) {
		return
	}

	mageRegistrationForm.fields.known_spells.push('')
}

function removeKnownSpellField(index: number) {
	if (mageRegistrationForm.fields.known_spells.length === 1) {
		mageRegistrationForm.fields.known_spells[0] = ''
		return
	}

	mageRegistrationForm.fields.known_spells.splice(index, 1)
}
</script>

<template layout>
	<div class="items-start gap-4 grid grid-cols-1 xl:grid-cols-3 overflow-hidden">
		<!-- mage registration -->
		<card title="Mage guild registration" description="Register yourself as a mage for guild admission.">
			<info-text :date="{ timeStyle: 'medium' }" label="Last updated" :content="lastMageRegistrationAt" />
			<form class="flex flex-col gap-y-8 my-8" @submit.prevent="mageRegistrationForm.submit()">
				<div class="gap-4 grid grid-cols-3">
					<!-- full name -->
					<u-form-field label="Full name" name="full_name" :error="mageRegistrationForm.errors.full_name">
						<u-input v-model="mageRegistrationForm.fields.full_name" placeholder="Frieren the Slayer" class="w-full" />
					</u-form-field>
					<!-- date of birth -->
					<u-form-field label="Date of birth" name="date_of_birth" :error="mageRegistrationForm.errors.date_of_birth">
						<u-input-date
							@update:model-value="(date) => mageRegistrationForm.fields.date_of_birth = date?.toString() ?? ''"
							class="w-full"
						/>
					</u-form-field>
					<!-- species -->
					<u-form-field label="Species" name="species" :error="mageRegistrationForm.errors.species">
						<u-select-menu
							v-model="mageRegistrationForm.fields.species"
							searchable
							value-key="value"
							label-key="label"
							placeholder="Select species..."
							class="w-full"
							:items="Object.entries(speciesOptions).map(([key, value]) => ({
								label: value,
								value: key,
							}))"
						/>
					</u-form-field>
				</div>
				<!-- known spells -->
				<u-form-field label="Known spells" name="known_spells" :error="knownSpellsError">
					<div class="flex flex-col gap-2">
						<div v-for="(_, index) in mageRegistrationForm.fields.known_spells" :key="index" class="flex gap-2">
							<!-- input -->
							<u-input
								v-model="mageRegistrationForm.fields.known_spells[index]"
								:placeholder="`Spell #${index + 1}`"
								class="w-full"
							/>
							<!-- remove -->
							<u-button
								v-if="index > 0"
								type="button"
								color="error"
								label="Remove"
								@click="removeKnownSpellField(index)"
							/>
						</div>
						<!-- add one -->
						<div class="flex justify-start w-full">
							<u-button
								type="button"
								icon="lucide:plus"
								color="neutral"
								label="Add another spell"
								class="w-min"
								:disabled="mageRegistrationForm.fields.known_spells.length >= 5"
								@click="addKnownSpellField()"
							/>
						</div>
					</div>
				</u-form-field>
				<div class="gap-4 grid grid-cols-2">
					<!-- expelled -->
					<u-form-field label="Have you ever been expelled from a magical order?" name="has_been_expelled">
						<u-checkbox v-model="mageRegistrationForm.fields.has_been_expelled" label="Yes" />
					</u-form-field>
					<!-- moral alignment -->
					<u-form-field
						label="What is your current moral alignment?"
						name="moral_alignment"
						:error="mageRegistrationForm.errors.moral_alignment"
					>
						<u-radio-group
							orientation="horizontal"
							v-model="mageRegistrationForm.fields.moral_alignment"
							:items="Object.entries(moralAlignmentOptions).map(([key, value]) => ({
								label: value,
								value: key,
							}))"
						/>
					</u-form-field>
				</div>

				<!-- additional comments -->
				<u-form-field label="Comments" name="comments" class="col-span-2" :error="mageRegistrationForm.errors.comments">
					<u-textarea
						v-model="mageRegistrationForm.fields.comments"
						placeholder="Optional additional details"
						class="w-full"
					/>
				</u-form-field>

				<!-- actions -->
				<div class="flex justify-end items-center gap-2">
					<u-button
						type="button"
						color="neutral"
						variant="subtle"
						label="Reset"
						@click="mageRegistrationForm.reset()"
					/>
					<u-button
						type="button"
						color="neutral"
						variant="subtle"
						label="Clear errors"
						@click="mageRegistrationForm.clearErrors()"
					/>
					<u-button
						type="button"
						color="primary"
						variant="subtle"
						label="Submit and don't reset form"
						:loading="mageRegistrationForm.processing"
						@click="mageRegistrationForm.submitWith({ reset: false })"
					/>
					<u-button
						type="submit"
						color="primary"
						variant="subtle"
						:loading="mageRegistrationForm.processing"
						label="Submit"
					/>
				</div>
			</form>
			<!-- debug -->
			<json-viewer
				:expanded="5"
				:data="{
					fields: mageRegistrationForm.fields,
					validation: mageRegistrationForm.errors,
				}"
			/>
		</card>
		<!-- spell discovery -->
		<card title="Spell discovery registration" description="Register a newly discovered spell.">
			<info-text :date="{ timeStyle: 'medium' }" label="Last updated" :content="lastSpellDiscoveryAt" />
			<form class="flex flex-col gap-4 my-8" @submit.prevent="spellDiscoveryForm.submit()">
				<div class="gap-4 grid grid-cols-3">
					<!-- mage id -->
					<u-form-field
						label="Mage reference ID"
						name="mage_reference_id"
						:error="spellDiscoveryForm.errors.mage_reference_id"
					>
						<u-input v-model="spellDiscoveryForm.fields.mage_reference_id" placeholder="MG-12A9-9981" class="w-full" />
					</u-form-field>
					<!-- spell name -->
					<u-form-field label="Name" name="spell_name" :error="spellDiscoveryForm.errors.spell_name">
						<u-input v-model="spellDiscoveryForm.fields.spell_name" placeholder="Zoltraak" class="w-full" />
					</u-form-field>
					<!-- risk level -->
					<u-form-field label="Risk level" name="spell_risk_level" :error="spellDiscoveryForm.errors.spell_risk_level">
						<u-select-menu
							placeholder="Select a risk level"
							v-model="spellDiscoveryForm.fields.spell_risk_level"
							searchable
							value-key="value"
							class="w-full"
							label-key="label"
							:items="Object.entries(spellRiskOptions).map(([key, value]) => ({
								label: value,
								value: key,
							}))"
						/>
					</u-form-field>
				</div>
				<!-- how it works -->
				<u-form-field
					label="How the spell works"
					name="spell_explanation"
					:error="spellDiscoveryForm.errors.spell_explanation"
				>
					<u-textarea
						v-model="spellDiscoveryForm.fields.spell_explanation"
						placeholder="Explain the mechanics and observed effects"
						class="w-full"
					/>
				</u-form-field>
				<!-- actions -->
				<div class="flex justify-end items-center gap-2">
					<u-button type="button" color="neutral" variant="subtle" label="Reset" @click="spellDiscoveryForm.reset()" />
					<u-button
						type="button"
						color="neutral"
						variant="subtle"
						label="Clear errors"
						@click="spellDiscoveryForm.clearErrors()"
					/>
					<u-button
						type="button"
						color="primary"
						variant="subtle"
						label="Submit and don't reset form"
						:loading="spellDiscoveryForm.processing"
						@click="spellDiscoveryForm.submitWith({ reset: false })"
					/>
					<u-button
						type="submit"
						color="primary"
						variant="subtle"
						:loading="spellDiscoveryForm.processing"
						label="Submit"
					/>
				</div>
			</form>
			<!-- debug -->
			<json-viewer
				:expanded="5"
				:data="{
					fields: spellDiscoveryForm.fields,
					validation: spellDiscoveryForm.errors,
				}"
			/>
		</card>
		<div class="flex flex-col gap-4">
			<!-- dialog -->
			<Card title="Form in dialog">
				<template #description>
					The <code class="text-toned">useValidation</code> and <code class="text-toned">useValidationBag</code>
					composables allow you to access validation errors globally.
				</template>
				<u-button :href="route('kitchen-sink.forms.validation.dialog')" label="Open dialog" />
			</Card>
			<!-- validation -->
			<Card title="Validation composables">
				<template #description>
					The <code class="text-toned">useValidation</code> and <code class="text-toned">useValidationBag</code>
					composables allow you to access validation errors globally.
				</template>
				<!-- validation -->
				<div>
					<span class="text-sm">Value of <code class="text-toned">useValidation()</code></span>
					<json-viewer :expanded="5" :data="validation" />
				</div>
				<!-- spellValidation -->
				<div class="mt-6">
					<span class="text-sm">Value of <code class="text-toned">useValidation('spell_discovery')</code></span>
					<json-viewer :expanded="5" :data="spellValidation" />
				</div>
			</Card>
		</div>
	</div>
</template>
