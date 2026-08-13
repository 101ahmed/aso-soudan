<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  createShuraMeeting,
  deleteShuraMeeting,
  fetchShuraMeetings,
} from '@/services/shura'

const { t, locale } = useI18n()
const auth = useAuthStore()
const items = ref([])
const error = ref('')
const canManage = () => auth.hasPermission('shura.meeting.manage')

const form = reactive({
  reference: '',
  title_ar: '',
  title_fr: '',
  scheduled_at: '',
  location: '',
  status: 'planned',
  visibility: 'internal',
  agenda_fr: '',
  agenda_ar: '',
})

async function load() {
  try {
    const data = await fetchShuraMeetings()
    items.value = data.data || []
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

async function save() {
  try {
    await createShuraMeeting({ ...form })
    Object.assign(form, {
      reference: '',
      title_ar: '',
      title_fr: '',
      scheduled_at: '',
      location: '',
      status: 'planned',
      visibility: 'internal',
      agenda_fr: '',
      agenda_ar: '',
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
      <h2 class="text-lg font-semibold">{{ t('shuraAdmin.meetings') }}</h2>
      <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>
      <article v-for="item in items" :key="item.id" class="rounded-lg border bg-white p-4">
        <p class="font-medium">{{ locale === 'ar' ? item.title_ar : (item.title_en || item.title_fr) }}</p>
        <p class="mt-1 text-xs text-slate-500">
          {{ item.reference || '—' }} · {{ item.scheduled_at || '—' }} · {{ item.visibility }} · {{ item.status }}
        </p>
        <button
          v-if="canManage()"
          type="button"
          class="mt-2 rounded border border-rose-300 px-2 py-1 text-xs text-rose-700"
          @click="deleteShuraMeeting(item.id).then(load)"
        >
          {{ t('forms.delete') }}
        </button>
      </article>
    </div>

    <form v-if="canManage()" class="space-y-3 rounded-xl border bg-white p-5" @submit.prevent="save">
      <h3 class="font-semibold">{{ t('shuraAdmin.newMeeting') }}</h3>
      <input v-model="form.reference" class="w-full rounded border px-3 py-2 text-sm" placeholder="08/2026" />
      <input v-model="form.title_fr" required class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.titleFr')" />
      <input v-model="form.title_ar" required class="w-full rounded border px-3 py-2 text-sm" dir="rtl" :placeholder="t('secretariatAdmin.titleAr')" />
      <input v-model="form.scheduled_at" type="datetime-local" class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.location" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('shuraAdmin.location')" />
      <select v-model="form.visibility" class="w-full rounded border px-3 py-2 text-sm">
        <option value="internal">INTERNAL</option>
        <option value="public">PUBLIC</option>
      </select>
      <select v-model="form.status" class="w-full rounded border px-3 py-2 text-sm">
        <option value="planned">planned</option>
        <option value="held">held</option>
        <option value="cancelled">cancelled</option>
      </select>
      <textarea v-model="form.agenda_fr" rows="3" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('shuraAdmin.agenda')" />
      <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white">{{ t('forms.save') }}</button>
    </form>
  </div>
</template>
