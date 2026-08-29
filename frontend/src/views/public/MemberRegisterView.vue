<script setup>
import { computed, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import PageHero from '@/components/public/PageHero.vue'
import { registerPublicMember } from '@/services/members'

const { t } = useI18n()
const step = ref(1)
const submitted = ref(false)
const sending = ref(false)
const error = ref('')

const membershipTypes = ['adherent', 'volunteer', 'supporter', 'student', 'family', 'other']

const form = reactive({
  first_name: '',
  last_name: '',
  birth_date: '',
  gender: '',
  email: '',
  phone: '',
  city: '',
  membership_type: 'adherent',
  consent: false,
})

const steps = computed(() => [
  t('register.member.steps.personal'),
  t('register.member.steps.contact'),
  t('register.member.steps.membership'),
  t('register.member.steps.consent'),
])

function canNext() {
  if (step.value === 1) return form.first_name.trim() && form.last_name.trim()
  if (step.value === 2) return form.email.trim()
  if (step.value === 3) return Boolean(form.membership_type)
  return true
}

function next() {
  if (step.value < 4 && canNext()) step.value += 1
}

function prev() {
  if (step.value > 1) step.value -= 1
}

async function submit() {
  if (!form.consent || sending.value) return
  sending.value = true
  error.value = ''
  try {
    await registerPublicMember({
      first_name: form.first_name,
      last_name: form.last_name,
      birth_date: form.birth_date || null,
      gender: form.gender || null,
      email: form.email,
      phone: form.phone || null,
      city: form.city || null,
      membership_type: form.membership_type || null,
    })
    submitted.value = true
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || t('register.member.error')
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <div>
    <PageHero :title="t('register.member.title')" :subtitle="t('register.member.subtitle')" />
    <section class="mx-auto max-w-2xl px-5 py-12 md:px-8">
      <div v-if="submitted" class="rounded-xl bg-white p-6 text-[var(--rdp-forest)]">
        <p class="font-semibold">{{ t('register.member.successTitle') }}</p>
        <p class="mt-2 text-sm">{{ t('register.member.successText') }}</p>
      </div>

      <div v-else class="rounded-xl bg-white p-6 shadow-sm">
        <p class="mb-4 text-sm text-slate-500">
          {{ t('register.step') }} {{ step }} / 4 — {{ steps[step - 1] }}
        </p>
        <p v-if="error" class="mb-4 rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>

        <div v-if="step === 1" class="space-y-3">
          <input v-model="form.first_name" required :placeholder="t('forms.firstName')" class="w-full rounded border px-3 py-2" />
          <input v-model="form.last_name" required :placeholder="t('forms.lastName')" class="w-full rounded border px-3 py-2" />
          <label class="block text-sm text-slate-600">
            {{ t('register.member.birthDate') }}
            <input v-model="form.birth_date" type="date" class="mt-1 w-full rounded border px-3 py-2 text-base text-slate-800" />
          </label>
          <select v-model="form.gender" class="w-full rounded border px-3 py-2">
            <option value="">{{ t('register.member.gender') }}</option>
            <option value="male">{{ t('register.member.genders.male') }}</option>
            <option value="female">{{ t('register.member.genders.female') }}</option>
          </select>
        </div>
        <div v-else-if="step === 2" class="space-y-3">
          <input v-model="form.email" type="email" required :placeholder="t('forms.email')" class="w-full rounded border px-3 py-2" />
          <input v-model="form.phone" :placeholder="t('forms.phone')" class="w-full rounded border px-3 py-2" />
          <input v-model="form.city" :placeholder="t('forms.city')" class="w-full rounded border px-3 py-2" />
        </div>
        <div v-else-if="step === 3" class="space-y-3">
          <select v-model="form.membership_type" required class="w-full rounded border px-3 py-2">
            <option v-for="type in membershipTypes" :key="type" :value="type">{{ t(`register.member.types.${type}`) }}</option>
          </select>
        </div>
        <div v-else class="space-y-3">
          <label class="flex items-start gap-2 text-sm">
            <input v-model="form.consent" type="checkbox" class="mt-1" />
            <span>{{ t('register.member.consent') }}</span>
          </label>
        </div>

        <div class="mt-6 flex flex-wrap gap-3">
          <button v-if="step > 1" type="button" class="rounded border px-4 py-2 text-sm" @click="prev">
            {{ t('admin.prev') }}
          </button>
          <button
            v-if="step < 4"
            type="button"
            class="rounded bg-[var(--rdp-forest)] px-4 py-2 text-sm text-white disabled:opacity-50"
            :disabled="!canNext()"
            @click="next"
          >
            {{ t('admin.next') }}
          </button>
          <button
            v-else
            type="button"
            class="rounded bg-[var(--rdp-forest)] px-4 py-2 text-sm text-white disabled:opacity-50"
            :disabled="!form.consent || sending"
            @click="submit"
          >
            {{ sending ? t('register.member.sending') : t('forms.send') }}
          </button>
        </div>
      </div>
    </section>
  </div>
</template>
