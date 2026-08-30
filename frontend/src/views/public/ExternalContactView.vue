<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import PageHero from '@/components/public/PageHero.vue'
import { fetchPublicPartners, submitExternalContact } from '@/services/external'

const { t, locale } = useI18n()
const submitted = ref(false)
const sending = ref(false)
const error = ref('')
const reference = ref('')
const partners = ref([])

const form = reactive({
  applicant_name: '',
  applicant_phone: '',
  applicant_email: '',
  applicant_organization: '',
  partner_id: '',
  partner_name: '',
  reason: '',
  consent: false,
})

function partnerLabel(item) {
  return locale.value === 'fr' ? (item.name_fr || item.name_ar) : (item.name_ar || item.name_fr)
}

async function submit() {
  if (!form.consent || sending.value) return
  sending.value = true
  error.value = ''
  try {
    const result = await submitExternalContact({
      applicant_name: form.applicant_name,
      applicant_phone: form.applicant_phone || null,
      applicant_email: form.applicant_email || null,
      applicant_organization: form.applicant_organization || null,
      partner_id: form.partner_id || null,
      partner_name: form.partner_name || null,
      reason: form.reason,
    })
    reference.value = result.reference || ''
    submitted.value = true
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || t('externalRel.error')
  } finally {
    sending.value = false
  }
}

onMounted(async () => {
  try {
    partners.value = await fetchPublicPartners()
  } catch {
    partners.value = []
  }
})
</script>

<template>
  <div>
    <PageHero :title="t('externalRel.publicTitle')" :subtitle="t('externalRel.publicSubtitle')" />
    <section class="mx-auto max-w-2xl px-5 py-12 md:px-8">
      <div v-if="submitted" class="rounded-xl bg-white p-6 text-[var(--rdp-forest)]">
        <p class="font-semibold">{{ t('externalRel.successTitle') }}</p>
        <p class="mt-2 text-sm">{{ t('externalRel.successText') }}</p>
        <p v-if="reference" class="mt-3 text-sm font-semibold">
          {{ t('socialHelp.reference') }}: {{ reference }}
        </p>
      </div>

      <form v-else class="space-y-3 rounded-xl bg-white p-6 shadow-sm" @submit.prevent="submit">
        <p class="text-sm text-slate-600">{{ t('externalRel.privacyHint') }}</p>
        <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
        <input v-model="form.applicant_name" required :placeholder="t('externalRel.applicant')" class="w-full rounded border px-3 py-2" />
        <input v-model="form.applicant_phone" :placeholder="t('forms.phone')" class="w-full rounded border px-3 py-2" />
        <input v-model="form.applicant_email" type="email" :placeholder="t('forms.email')" class="w-full rounded border px-3 py-2" />
        <input v-model="form.applicant_organization" :placeholder="t('externalRel.applicantOrg')" class="w-full rounded border px-3 py-2" />
        <select v-model="form.partner_id" class="w-full rounded border px-3 py-2">
          <option value="">{{ t('externalRel.choosePartner') }}</option>
          <option v-for="p in partners" :key="p.id" :value="p.id">{{ partnerLabel(p) }}</option>
        </select>
        <input v-model="form.partner_name" :placeholder="t('externalRel.partnerNameFree')" class="w-full rounded border px-3 py-2" />
        <textarea
          v-model="form.reason"
          required
          rows="5"
          :placeholder="t('externalRel.reason')"
          class="w-full rounded border px-3 py-2"
        />
        <label class="flex items-start gap-2 text-sm">
          <input v-model="form.consent" type="checkbox" class="mt-1" />
          <span>{{ t('externalRel.consent') }}</span>
        </label>
        <button
          type="submit"
          class="rounded bg-[var(--rdp-forest)] px-4 py-2 text-sm font-semibold text-white disabled:opacity-50"
          :disabled="!form.consent || sending"
        >
          {{ sending ? t('register.member.sending') : t('externalRel.submit') }}
        </button>
      </form>
    </section>
  </div>
</template>
