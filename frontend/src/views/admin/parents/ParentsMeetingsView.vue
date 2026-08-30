<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { createParentMeeting, deleteParentMeeting, fetchParentMeetings } from '@/services/parents'

const { t, locale } = useI18n()
const auth = useAuthStore()
const items = ref([])
const error = ref('')
const canManage = () => auth.hasPermission('parents.meeting.manage')

const form = reactive({
  title_ar: '',
  title_fr: '',
  scheduled_at: '',
  location: '',
  map_url: '',
  status: 'planned',
  visibility: 'public',
  agenda_ar: '',
  agenda_fr: '',
})

async function load() {
  error.value = ''
  try {
    const data = await fetchParentMeetings()
    items.value = data.data || []
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

async function save() {
  error.value = ''
  try {
    await createParentMeeting({ ...form, scheduled_at: form.scheduled_at || null, map_url: form.map_url || null })
    Object.assign(form, {
      title_ar: '',
      title_fr: '',
      scheduled_at: '',
      location: '',
      map_url: '',
      status: 'planned',
      visibility: 'public',
      agenda_ar: '',
      agenda_fr: '',
    })
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

onMounted(load)
</script>

<template>
  <div class="grid gap-6 lg:grid-cols-2">
    <div class="space-y-3">
      <h2 class="text-lg font-semibold">{{ t('parentsAdmin.meetings') }}</h2>
      <p class="text-sm text-slate-600">{{ t('parentsAdmin.meetingsHint') }}</p>
      <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
      <article v-for="item in items" :key="item.id" class="rounded-lg border bg-white p-4">
        <p class="font-medium">{{ locale === 'ar' ? item.title_ar : item.title_fr }}</p>
        <p class="mt-1 text-xs text-slate-500">
          {{ item.scheduled_at || '—' }} · {{ item.location || '—' }} · {{ item.visibility }}
        </p>
        <a
          v-if="item.map_url"
          :href="item.map_url"
          target="_blank"
          rel="noopener"
          class="mt-2 inline-flex text-sm font-semibold text-teal-800 hover:underline"
        >
          {{ t('parents.mapLink') }}
        </a>
        <button
          v-if="canManage()"
          type="button"
          class="mt-2 block text-xs text-rose-700 hover:underline"
          @click="deleteParentMeeting(item.id).then(load)"
        >
          {{ t('forms.delete') }}
        </button>
      </article>
    </div>

    <form v-if="canManage()" class="space-y-3 rounded-xl border bg-white p-5" @submit.prevent="save">
      <h3 class="font-semibold">{{ t('parentsAdmin.newMeeting') }}</h3>
      <input v-model="form.title_fr" required class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.titleFr')" />
      <input v-model="form.title_ar" required class="w-full rounded border px-3 py-2 text-sm" dir="rtl" :placeholder="t('secretariatAdmin.titleAr')" />
      <input v-model="form.scheduled_at" type="datetime-local" class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.location" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('parentsAdmin.location')" />
      <input v-model="form.map_url" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('parentsAdmin.mapUrl')" />
      <select v-model="form.visibility" class="w-full rounded border px-3 py-2 text-sm">
        <option value="public">{{ t('parentsAdmin.visibilityPublic') }}</option>
        <option value="internal">{{ t('parentsAdmin.visibilityInternal') }}</option>
      </select>
      <select v-model="form.status" class="w-full rounded border px-3 py-2 text-sm">
        <option value="planned">{{ t('parentsAdmin.meetingStatuses.planned') }}</option>
        <option value="held">{{ t('parentsAdmin.meetingStatuses.held') }}</option>
        <option value="cancelled">{{ t('parentsAdmin.meetingStatuses.cancelled') }}</option>
      </select>
      <textarea v-model="form.agenda_ar" rows="3" class="w-full rounded border px-3 py-2 text-sm" dir="rtl" :placeholder="t('parentsAdmin.agendaAr')" />
      <textarea v-model="form.agenda_fr" rows="3" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('parentsAdmin.agendaFr')" />
      <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white">{{ t('forms.save') }}</button>
    </form>
  </div>
</template>
