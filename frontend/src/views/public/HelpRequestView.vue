<script setup>
import { reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import PageHero from '@/components/public/PageHero.vue'
import { submitHelpRequest } from '@/services/helpRequests'
import { RENNES_CITY, RENNES_SUBURBS } from '@/data/rennesMetropole'

const { t } = useI18n()
const submitted = ref(false)
const sending = ref(false)
const error = ref('')
const reference = ref('')

const helpTypes = [
  'financial',
  'food',
  'housing',
  'admin_papers',
  'health',
  'family',
  'ramadan',
  'emergency',
  'other',
]

const form = reactive({
  full_name: '',
  phone: '',
  email: '',
  city: '',
  help_type: '',
  details: '',
  family_size: '',
  consent: false,
})

async function submit() {
  if (!form.consent || sending.value) return
  sending.value = true
  error.value = ''
  try {
    const result = await submitHelpRequest({
      full_name: form.full_name,
      phone: form.phone,
      email: form.email || null,
      city: form.city || null,
      help_type: form.help_type,
      details: form.details,
      family_size: form.family_size ? Number(form.family_size) : null,
    })
    reference.value = result.reference || ''
    submitted.value = true
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || t('socialHelp.error')
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <div>
    <PageHero :title="t('socialHelp.publicTitle')" :subtitle="t('socialHelp.publicSubtitle')" />
    <section class="mx-auto max-w-2xl px-5 py-12 md:px-8">
      <div v-if="submitted" class="rounded-xl bg-white p-6 text-[var(--rdp-forest)]">
        <p class="font-semibold">{{ t('socialHelp.successTitle') }}</p>
        <p class="mt-2 text-sm">{{ t('socialHelp.successText') }}</p>
        <p v-if="reference" class="mt-3 text-sm font-semibold">
          {{ t('socialHelp.reference') }}: {{ reference }}
        </p>
      </div>

      <form v-else class="space-y-3 rounded-xl bg-white p-6 shadow-sm" @submit.prevent="submit">
        <p class="text-sm text-slate-600">{{ t('socialHelp.privacyHint') }}</p>
        <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
        <input v-model="form.full_name" required :placeholder="t('forms.name')" class="w-full rounded border px-3 py-2" />
        <input v-model="form.phone" required :placeholder="t('forms.phone')" class="w-full rounded border px-3 py-2" />
        <input v-model="form.email" type="email" :placeholder="t('forms.email')" class="w-full rounded border px-3 py-2" />
        <select v-model="form.city" class="w-full rounded border px-3 py-2">
          <option value="">{{ t('forms.city') }}</option>
          <option :value="RENNES_CITY">{{ t('register.member.rennes') }}</option>
          <optgroup :label="t('register.member.suburbsGroup')">
            <option v-for="city in RENNES_SUBURBS" :key="city" :value="city">{{ city }}</option>
          </optgroup>
        </select>
        <select v-model="form.help_type" required class="w-full rounded border px-3 py-2">
          <option value="">{{ t('socialHelp.helpType') }}</option>
          <option v-for="type in helpTypes" :key="type" :value="type">{{ t(`socialHelp.types.${type}`) }}</option>
        </select>
        <input
          v-model="form.family_size"
          type="number"
          min="1"
          max="30"
          :placeholder="t('socialHelp.familySize')"
          class="w-full rounded border px-3 py-2"
        />
        <textarea
          v-model="form.details"
          required
          rows="5"
          :placeholder="t('socialHelp.details')"
          class="w-full rounded border px-3 py-2"
        />
        <label class="flex items-start gap-2 text-sm">
          <input v-model="form.consent" type="checkbox" class="mt-1" />
          <span>{{ t('socialHelp.consent') }}</span>
        </label>
        <button
          type="submit"
          class="rounded bg-[var(--rdp-forest)] px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
          :disabled="!form.consent || sending"
        >
          {{ sending ? t('register.member.sending') : t('socialHelp.submit') }}
        </button>
      </form>
    </section>
  </div>
</template>
