<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { pickTitle } from '@/utils/localized'
import {
  createPresidentMeeting,
  deletePresidentMeeting,
  fetchPresidentMeetings,
  updatePresidentMeeting,
} from '@/services/president'

const { t, locale } = useI18n()
const route = useRoute()
const items = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const editingId = ref(null)

const filters = reactive({
  scope: route.query.scope ?? (route.query.classification ? '' : 'upcoming'),
  classification: route.query.classification || '',
  status: '',
  search: '',
})

const form = reactive(emptyForm())

function emptyForm() {
  return {
    title_ar: '',
    title_fr: '',
    scheduled_at: '',
    location: '',
    classification: 'info',
    status: 'upcoming',
    agenda_ar: '',
    agenda_fr: '',
    minutes_ar: '',
    minutes_fr: '',
    decisions_ar: '',
    decisions_fr: '',
    follow_up_ar: '',
    follow_up_fr: '',
    decision_number: '',
  }
}

function resetForm() {
  editingId.value = null
  Object.assign(form, emptyForm())
}

function toLocalInput(value) {
  if (!value) return ''
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return String(value).slice(0, 16)
  const pad = (n) => String(n).padStart(2, '0')
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`
}

function edit(item) {
  editingId.value = item.id
  form.title_ar = item.title_ar || ''
  form.title_fr = item.title_fr || ''
  form.scheduled_at = toLocalInput(item.scheduled_at)
  form.location = item.location || ''
  form.classification = item.classification || 'info'
  form.status = item.status || 'upcoming'
  form.agenda_ar = item.agenda_ar || ''
  form.agenda_fr = item.agenda_fr || ''
  form.minutes_ar = item.minutes_ar || ''
  form.minutes_fr = item.minutes_fr || ''
  form.decisions_ar = item.decisions_ar || ''
  form.decisions_fr = item.decisions_fr || ''
  form.follow_up_ar = item.follow_up_ar || ''
  form.follow_up_fr = item.follow_up_fr || ''
  form.decision_number = item.decision_number || ''
}

function badgeClass(classification) {
  if (classification === 'urgent') return 'bg-red-100 text-red-800'
  if (classification === 'follow_up') return 'bg-amber-100 text-amber-900'
  return 'bg-emerald-100 text-emerald-800'
}

function formatDate(value) {
  if (!value) return '—'
  return String(value).slice(0, 16).replace('T', ' ')
}

const payload = computed(() => ({
  ...form,
  scheduled_at: form.scheduled_at || null,
  decision_number: form.decision_number || null,
}))

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await fetchPresidentMeetings({
      scope: filters.scope || undefined,
      classification: filters.classification || undefined,
      status: filters.status || undefined,
      search: filters.search || undefined,
    })
    items.value = data.data || []
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  error.value = ''
  try {
    if (editingId.value) {
      await updatePresidentMeeting(editingId.value, payload.value)
    } else {
      await createPresidentMeeting(payload.value)
    }
    resetForm()
    await load()
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || e.message
  } finally {
    saving.value = false
  }
}

async function remove(id) {
  if (!confirm(t('presidentAdmin.confirmDelete'))) return
  await deletePresidentMeeting(id)
  if (editingId.value === id) resetForm()
  await load()
}

watch(filters, load, { deep: true })
onMounted(load)
</script>

<template>
  <div class="grid gap-6 lg:grid-cols-[1.15fr_1fr]">
    <div class="space-y-3">
      <h2 class="text-lg font-semibold">{{ t('presidentAdmin.meetings') }} 🗓️</h2>
      <p class="text-sm text-slate-600">{{ t('presidentAdmin.meetingsHint') }}</p>

      <div class="grid gap-2 sm:grid-cols-2">
        <select v-model="filters.scope" class="rounded border px-3 py-2 text-sm">
          <option value="upcoming">{{ t('presidentAdmin.upcoming') }}</option>
          <option value="">{{ t('presidentAdmin.allMeetings') }}</option>
        </select>
        <select v-model="filters.classification" class="rounded border px-3 py-2 text-sm">
          <option value="">{{ t('presidentAdmin.allClassifications') }}</option>
          <option value="urgent">{{ t('presidentAdmin.classifications.urgent') }}</option>
          <option value="follow_up">{{ t('presidentAdmin.classifications.follow_up') }}</option>
          <option value="info">{{ t('presidentAdmin.classifications.info') }}</option>
        </select>
        <input v-model="filters.search" class="rounded border px-3 py-2 text-sm sm:col-span-2" :placeholder="t('presidentAdmin.searchMeetings')" />
      </div>

      <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>
      <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>

      <article v-for="item in items" :key="item.id" class="rounded-lg border bg-white p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p class="text-xs text-slate-500">{{ item.reference }} · {{ formatDate(item.scheduled_at) }}</p>
            <p class="font-medium text-[var(--rdp-forest)]">{{ pickTitle(item, locale) }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ item.location || '—' }} · {{ t(`presidentAdmin.meetingStatuses.${item.status}`) }}</p>
          </div>
          <span class="rounded px-2 py-1 text-xs font-medium" :class="badgeClass(item.classification)">
            {{ t(`presidentAdmin.classifications.${item.classification}`) }}
          </span>
        </div>
        <p v-if="item.agenda_ar || item.agenda_fr" class="mt-2 whitespace-pre-line text-sm text-slate-700">
          <strong>{{ t('presidentAdmin.agenda') }}:</strong>
          {{ locale === 'ar' ? (item.agenda_ar || item.agenda_fr) : (item.agenda_fr || item.agenda_ar) }}
        </p>
        <p v-if="item.decisions_ar || item.decisions_fr" class="mt-1 whitespace-pre-line text-sm text-slate-700">
          <strong>{{ t('presidentAdmin.decisions') }}:</strong>
          {{ locale === 'ar' ? (item.decisions_ar || item.decisions_fr) : (item.decisions_fr || item.decisions_ar) }}
        </p>
        <p v-if="item.follow_up_ar || item.follow_up_fr" class="mt-1 whitespace-pre-line text-sm text-slate-700">
          <strong>{{ t('presidentAdmin.followUp') }}:</strong>
          {{ locale === 'ar' ? (item.follow_up_ar || item.follow_up_fr) : (item.follow_up_fr || item.follow_up_ar) }}
        </p>
        <div class="mt-3 flex gap-2">
          <button type="button" class="rounded border px-2 py-1 text-xs" @click="edit(item)">{{ t('forms.edit') }}</button>
          <button type="button" class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700" @click="remove(item.id)">
            {{ t('forms.delete') }}
          </button>
        </div>
      </article>
      <p v-if="!loading && !items.length" class="text-sm text-slate-500">{{ t('presidentAdmin.emptyMeetings') }}</p>
    </div>

    <form class="space-y-3 rounded-xl border bg-white p-5" @submit.prevent="save">
      <h3 class="font-semibold">{{ editingId ? t('presidentAdmin.editMeeting') : t('presidentAdmin.newMeeting') }}</h3>
      <input v-model="form.title_ar" required class="w-full rounded border px-3 py-2 text-sm" dir="rtl" :placeholder="t('presidentAdmin.titleAr')" />
      <input v-model="form.title_fr" required class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('presidentAdmin.titleFr')" />
      <input v-model="form.scheduled_at" type="datetime-local" class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.location" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('presidentAdmin.location')" />
      <div class="grid gap-2 sm:grid-cols-2">
        <select v-model="form.classification" class="rounded border px-3 py-2 text-sm">
          <option value="urgent">{{ t('presidentAdmin.classifications.urgent') }}</option>
          <option value="follow_up">{{ t('presidentAdmin.classifications.follow_up') }}</option>
          <option value="info">{{ t('presidentAdmin.classifications.info') }}</option>
        </select>
        <select v-model="form.status" class="rounded border px-3 py-2 text-sm">
          <option value="upcoming">{{ t('presidentAdmin.meetingStatuses.upcoming') }}</option>
          <option value="held">{{ t('presidentAdmin.meetingStatuses.held') }}</option>
          <option value="cancelled">{{ t('presidentAdmin.meetingStatuses.cancelled') }}</option>
        </select>
      </div>
      <textarea v-model="form.agenda_ar" rows="3" class="w-full rounded border px-3 py-2 text-sm" dir="rtl" :placeholder="t('presidentAdmin.agendaAr')" />
      <textarea v-model="form.agenda_fr" rows="2" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('presidentAdmin.agendaFr')" />
      <textarea v-model="form.minutes_ar" rows="3" class="w-full rounded border px-3 py-2 text-sm" dir="rtl" :placeholder="t('presidentAdmin.minutesAr')" />
      <textarea v-model="form.minutes_fr" rows="2" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('presidentAdmin.minutesFr')" />
      <textarea v-model="form.decisions_ar" rows="3" class="w-full rounded border px-3 py-2 text-sm" dir="rtl" :placeholder="t('presidentAdmin.decisionsAr')" />
      <textarea v-model="form.decisions_fr" rows="2" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('presidentAdmin.decisionsFr')" />
      <input v-model="form.decision_number" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('presidentAdmin.decisionNumber')" />
      <textarea v-model="form.follow_up_ar" rows="3" class="w-full rounded border px-3 py-2 text-sm" dir="rtl" :placeholder="t('presidentAdmin.followUpAr')" />
      <textarea v-model="form.follow_up_fr" rows="2" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('presidentAdmin.followUpFr')" />
      <div class="flex gap-2">
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white disabled:opacity-50" :disabled="saving">
          {{ t('forms.save') }}
        </button>
        <button type="button" class="rounded border px-4 py-2 text-sm" @click="resetForm">{{ t('forms.cancel') }}</button>
      </div>
    </form>
  </div>
</template>
