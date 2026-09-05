<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { pickTitle } from '@/utils/localized'
import {
  createMeetingOutput,
  deleteMeetingOutput,
  fetchMeetingOutputs,
  updateMeetingOutput,
} from '@/services/meetingOutputs'

const route = useRoute()
const { t, locale } = useI18n()
const auth = useAuthStore()
const code = computed(() => route.params.code)
const isGeneral = computed(() => code.value === 'general')

const items = ref([])
const loading = ref(false)
const error = ref('')
const editingId = ref(null)
const formBox = ref(null)
const search = ref('')

const canCreate = computed(() => auth.hasPermission('decision.create'))
const canUpdate = computed(() => auth.hasPermission('decision.update'))
const canDelete = computed(() => auth.hasPermission('decision.delete'))
const canManage = computed(() => (editingId.value ? canUpdate.value : canCreate.value))

const form = reactive(emptyForm())

function emptyForm() {
  return {
    title_ar: '',
    title_fr: '',
    meeting_on: new Date().toISOString().slice(0, 10),
    location: '',
    attendees_ar: '',
    attendees_fr: '',
    agenda_ar: '',
    agenda_fr: '',
    outputs_ar: '',
    outputs_fr: '',
    follow_up_ar: '',
    follow_up_fr: '',
    notes: '',
    is_public: false,
  }
}

function resetForm() {
  editingId.value = null
  Object.assign(form, emptyForm())
}

function startNew() {
  resetForm()
  formBox.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

function localizedText(item, arKey, frKey) {
  if (locale.value === 'ar') return item[arKey] || item[frKey] || ''
  return item[frKey] || item[arKey] || ''
}

function edit(item) {
  editingId.value = item.id
  form.title_ar = item.title_ar || ''
  form.title_fr = item.title_fr || ''
  form.meeting_on = item.meeting_on || ''
  form.location = item.location || ''
  form.attendees_ar = item.attendees_ar || ''
  form.attendees_fr = item.attendees_fr || ''
  form.agenda_ar = item.agenda_ar || ''
  form.agenda_fr = item.agenda_fr || ''
  form.outputs_ar = item.outputs_ar || ''
  form.outputs_fr = item.outputs_fr || ''
  form.follow_up_ar = item.follow_up_ar || ''
  form.follow_up_fr = item.follow_up_fr || ''
  form.notes = item.notes || ''
  form.is_public = !!item.is_public
  formBox.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

async function load() {
  if (!isGeneral.value) return
  loading.value = true
  error.value = ''
  try {
    const data = await fetchMeetingOutputs(code.value, {
      search: search.value || undefined,
    })
    items.value = data.data || []
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

async function save() {
  if (!canManage.value) return
  error.value = ''
  try {
    const payload = {
      ...form,
      location: form.location || null,
      attendees_ar: form.attendees_ar || null,
      attendees_fr: form.attendees_fr || null,
      agenda_ar: form.agenda_ar || null,
      agenda_fr: form.agenda_fr || null,
      outputs_ar: form.outputs_ar || null,
      outputs_fr: form.outputs_fr || null,
      follow_up_ar: form.follow_up_ar || null,
      follow_up_fr: form.follow_up_fr || null,
      notes: form.notes || null,
    }
    if (editingId.value) await updateMeetingOutput(code.value, editingId.value, payload)
    else await createMeetingOutput(code.value, payload)
    resetForm()
    await load()
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || e.message
  }
}

async function remove(id) {
  if (!canDelete.value) return
  if (!confirm(t('secretariatAdmin.confirmDelete'))) return
  await deleteMeetingOutput(code.value, id)
  if (editingId.value === id) resetForm()
  await load()
}

onMounted(load)
</script>

<template>
  <p v-if="!isGeneral" class="rounded-xl border border-amber-200 bg-amber-50 p-6 text-sm text-amber-900">
    {{ t('secretariatAdmin.meetingOutputsOnlyGeneral') }}
  </p>

  <div v-else class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
    <div class="space-y-3">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h2 class="text-lg font-semibold">{{ t('secretariatAdmin.meetingOutputs') }}</h2>
          <p class="mt-1 text-sm text-slate-600">{{ t('secretariatAdmin.meetingOutputsHint') }}</p>
        </div>
        <button
          v-if="canCreate"
          type="button"
          class="rounded bg-teal-800 px-3 py-2 text-sm font-semibold text-white"
          @click="startNew"
        >
          {{ t('secretariatAdmin.meetingOutputsNew') }}
        </button>
      </div>

      <input
        v-model="search"
        :placeholder="t('secretariatAdmin.meetingOutputsSearch')"
        class="w-full rounded border px-3 py-2 text-sm"
        @change="load"
      />

      <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>
      <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
      <p v-else-if="!loading && !items.length" class="text-sm text-slate-500">{{ t('secretariatAdmin.meetingOutputsEmpty') }}</p>

      <article v-for="item in items" :key="item.id" class="rounded-lg border border-slate-200 bg-white p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="text-xs text-slate-500">{{ item.reference }} · {{ item.meeting_on }}</p>
            <p class="font-medium">{{ pickTitle(item, locale) }}</p>
            <p v-if="item.location" class="mt-1 text-sm text-slate-600">{{ item.location }}</p>
            <p
              v-if="localizedText(item, 'outputs_ar', 'outputs_fr')"
              class="mt-2 line-clamp-3 whitespace-pre-line text-sm text-slate-700"
            >
              {{ localizedText(item, 'outputs_ar', 'outputs_fr') }}
            </p>
            <p
              class="mt-2 text-xs font-medium"
              :class="item.is_public ? 'text-teal-800' : 'text-slate-500'"
            >
              {{ item.is_public ? t('secretariatAdmin.publishPublic') : t('secretariatAdmin.internalOnly') }}
            </p>
          </div>
          <div class="flex gap-1">
            <button v-if="canUpdate" type="button" class="rounded border px-2 py-1 text-xs" @click="edit(item)">
              {{ t('forms.edit') }}
            </button>
            <button
              v-if="canDelete"
              type="button"
              class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700"
              @click="remove(item.id)"
            >
              {{ t('forms.delete') }}
            </button>
          </div>
        </div>
      </article>
    </div>

    <form
      v-if="canManage"
      ref="formBox"
      class="space-y-3 rounded-xl border bg-white p-5"
      @submit.prevent="save"
    >
      <h3 class="font-semibold">
        {{ editingId ? t('secretariatAdmin.meetingOutputsEdit') : t('secretariatAdmin.meetingOutputsNew') }}
      </h3>
      <label class="block text-sm">
        <span class="mb-1 block text-slate-600">{{ t('secretariatAdmin.meetingTitleAr') }}</span>
        <input v-model="form.title_ar" required dir="rtl" class="w-full rounded border px-3 py-2 text-sm" />
      </label>
      <label class="block text-sm">
        <span class="mb-1 block text-slate-600">{{ t('secretariatAdmin.meetingTitleFr') }}</span>
        <input v-model="form.title_fr" required class="w-full rounded border px-3 py-2 text-sm" />
      </label>
      <label class="block text-sm">
        <span class="mb-1 block text-slate-600">{{ t('secretariatAdmin.meetingOn') }}</span>
        <input v-model="form.meeting_on" type="date" required class="w-full rounded border px-3 py-2 text-sm" />
      </label>
      <label class="block text-sm">
        <span class="mb-1 block text-slate-600">{{ t('secretariatAdmin.meetingLocation') }}</span>
        <input v-model="form.location" class="w-full rounded border px-3 py-2 text-sm" />
      </label>
      <label class="block text-sm">
        <span class="mb-1 block text-slate-600">{{ t('secretariatAdmin.attendeesAr') }}</span>
        <textarea v-model="form.attendees_ar" rows="2" dir="rtl" class="w-full rounded border px-3 py-2 text-sm" />
      </label>
      <label class="block text-sm">
        <span class="mb-1 block text-slate-600">{{ t('secretariatAdmin.attendeesFr') }}</span>
        <textarea v-model="form.attendees_fr" rows="2" class="w-full rounded border px-3 py-2 text-sm" />
      </label>
      <label class="block text-sm">
        <span class="mb-1 block text-slate-600">{{ t('secretariatAdmin.agendaAr') }}</span>
        <textarea v-model="form.agenda_ar" rows="3" dir="rtl" class="w-full rounded border px-3 py-2 text-sm" />
      </label>
      <label class="block text-sm">
        <span class="mb-1 block text-slate-600">{{ t('secretariatAdmin.agendaFr') }}</span>
        <textarea v-model="form.agenda_fr" rows="3" class="w-full rounded border px-3 py-2 text-sm" />
      </label>
      <label class="block text-sm">
        <span class="mb-1 block text-slate-600">{{ t('secretariatAdmin.outputsAr') }}</span>
        <textarea v-model="form.outputs_ar" rows="4" dir="rtl" class="w-full rounded border px-3 py-2 text-sm" />
      </label>
      <label class="block text-sm">
        <span class="mb-1 block text-slate-600">{{ t('secretariatAdmin.outputsFr') }}</span>
        <textarea v-model="form.outputs_fr" rows="4" class="w-full rounded border px-3 py-2 text-sm" />
      </label>
      <label class="block text-sm">
        <span class="mb-1 block text-slate-600">{{ t('secretariatAdmin.followUpAr') }}</span>
        <textarea v-model="form.follow_up_ar" rows="2" dir="rtl" class="w-full rounded border px-3 py-2 text-sm" />
      </label>
      <label class="block text-sm">
        <span class="mb-1 block text-slate-600">{{ t('secretariatAdmin.followUpFr') }}</span>
        <textarea v-model="form.follow_up_fr" rows="2" class="w-full rounded border px-3 py-2 text-sm" />
      </label>
      <textarea
        v-model="form.notes"
        rows="2"
        class="w-full rounded border px-3 py-2 text-sm"
        :placeholder="t('secretariatAdmin.meetingNotes')"
      />
      <label class="flex items-center gap-2 text-sm">
        <input v-model="form.is_public" type="checkbox" />
        {{ t('secretariatAdmin.publishPublic') }}
      </label>
      <div class="flex gap-2">
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white">{{ t('forms.save') }}</button>
        <button type="button" class="rounded border px-4 py-2 text-sm" @click="resetForm">{{ t('forms.cancel') }}</button>
      </div>
    </form>
  </div>
</template>
