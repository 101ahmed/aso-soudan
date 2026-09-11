<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import PageHero from '@/components/public/PageHero.vue'
import { pickName } from '@/utils/localized'
import { AGE_CATEGORIES, fetchPublicSports, submitSportsJoin } from '@/services/sports'
import { RENNES_CITY, RENNES_SUBURBS } from '@/data/rennesMetropole'

const { t, locale } = useI18n()
const submitted = ref(false)
const sending = ref(false)
const error = ref('')
const reference = ref('')
const teams = ref([])

const form = reactive({
  full_name: '',
  birth_date: '',
  email: '',
  phone: '',
  city: '',
  age_category: '',
  position: '',
  sports_team_id: '',
  for_national: false,
  message: '',
  consent: false,
})

onMounted(async () => {
  try {
    const data = await fetchPublicSports()
    teams.value = data.teams || []
  } catch {
    teams.value = []
  }
})

async function submit() {
  if (!form.consent || sending.value) return
  sending.value = true
  error.value = ''
  try {
    const result = await submitSportsJoin({
      full_name: form.full_name,
      birth_date: form.birth_date || null,
      email: form.email || null,
      phone: form.phone,
      city: form.city || null,
      age_category: form.age_category || null,
      position: form.position || null,
      sports_team_id: form.sports_team_id || null,
      for_national: form.for_national,
      message: form.message || null,
    })
    reference.value = result.reference || ''
    submitted.value = true
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || t('sportsAmanah.joinError')
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <div>
    <PageHero :title="t('sportsAmanah.joinTitle')" :subtitle="t('sportsAmanah.joinSubtitle')" />
    <section class="mx-auto max-w-2xl px-5 py-12 md:px-8">
      <div v-if="submitted" class="rounded-xl bg-white p-6 text-[var(--rdp-forest)]">
        <p class="font-semibold">{{ t('sportsAmanah.joinSuccessTitle') }}</p>
        <p class="mt-2 text-sm">{{ t('sportsAmanah.joinSuccessText') }}</p>
        <p v-if="reference" class="mt-3 text-sm font-semibold">{{ t('sportsAmanah.reference') }}: {{ reference }}</p>
      </div>
      <form v-else class="space-y-3 rounded-xl bg-white p-6 shadow-sm" @submit.prevent="submit">
        <p class="text-sm text-slate-600">{{ t('sportsAmanah.joinPrivacy') }}</p>
        <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
        <input v-model="form.full_name" required :placeholder="t('forms.name')" class="w-full rounded border px-3 py-2" />
        <input v-model="form.phone" required :placeholder="t('forms.phone')" class="w-full rounded border px-3 py-2" />
        <input v-model="form.email" type="email" :placeholder="t('forms.email')" class="w-full rounded border px-3 py-2" />
        <input v-model="form.birth_date" type="date" class="w-full rounded border px-3 py-2" />
        <select v-model="form.city" class="w-full rounded border px-3 py-2">
          <option value="">{{ t('forms.city') }}</option>
          <option :value="RENNES_CITY">{{ t('register.member.rennes') }}</option>
          <optgroup :label="t('register.member.suburbsGroup')">
            <option v-for="city in RENNES_SUBURBS" :key="city" :value="city">{{ city }}</option>
          </optgroup>
        </select>
        <select v-model="form.age_category" class="w-full rounded border px-3 py-2">
          <option value="">{{ t('sportsAmanah.ageCategory') }}</option>
          <option v-for="age in AGE_CATEGORIES" :key="age" :value="age">{{ t(`sportsAmanah.ages.${age}`) }}</option>
        </select>
        <select v-model="form.sports_team_id" class="w-full rounded border px-3 py-2">
          <option value="">{{ t('sportsAmanah.chooseTeam') }}</option>
          <option v-for="team in teams" :key="team.id" :value="team.id">{{ pickName(team, locale) }}</option>
        </select>
        <input v-model="form.position" :placeholder="t('sportsAmanah.position')" class="w-full rounded border px-3 py-2" />
        <label class="flex items-center gap-2 text-sm">
          <input v-model="form.for_national" type="checkbox" />
          {{ t('sportsAmanah.forNational') }}
        </label>
        <textarea v-model="form.message" rows="4" :placeholder="t('sportsAmanah.joinMessage')" class="w-full rounded border px-3 py-2" />
        <label class="flex items-start gap-2 text-sm">
          <input v-model="form.consent" type="checkbox" class="mt-1" />
          <span>{{ t('sportsAmanah.joinConsent') }}</span>
        </label>
        <button type="submit" :disabled="!form.consent || sending" class="rounded bg-[var(--rdp-forest)] px-5 py-3 text-sm font-semibold text-white disabled:opacity-50">
          {{ sending ? t('register.member.sending') : t('sportsAmanah.joinSubmit') }}
        </button>
      </form>
    </section>
  </div>
</template>
