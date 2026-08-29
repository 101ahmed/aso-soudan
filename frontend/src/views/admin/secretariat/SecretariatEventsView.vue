<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { pickTitle } from '@/utils/localized'
import {
  archiveEvent,
  createEvent,
  deleteEvent,
  fetchDepartmentEvents,
  publishEvent,
  updateEvent,
} from '@/services/content'

const route = useRoute()
const { t, locale } = useI18n()
const auth = useAuthStore()
const code = computed(() => route.params.code)
const items = ref([])
const loading = ref(false)
const error = ref('')
const editingId = ref(null)

const form = reactive({
  type: 'activity',
  title_ar: '',
  title_fr: '',
  description_ar: '',
  description_fr: '',
  location_ar: '',
  location_fr: '',
  starts_at: '',
  ends_at: '',
  status: 'draft',
  show_on_secretariat: true,
  show_on_home: false,
  image: null,
})

const canPublish = computed(
  () => auth.hasPermission('event.publish') || auth.hasPermission('event.create'),
)

const types = ['seminar', 'lecture', 'activity']

function titleOf(item) {
  return pickTitle(item, locale.value)
}

function typeLabel(type) {
  return t(`secretariatAdmin.eventTypes.${type}`)
}

function toLocalInput(iso) {
  if (!iso) return ''
  const d = new Date(iso)
  if (Number.isNaN(d.getTime())) return ''
  const pad = (n) => String(n).padStart(2, '0')
  return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`
}

function resetForm() {
  editingId.value = null
  form.type = 'activity'
  form.title_ar = ''
  form.title_fr = ''
  form.description_ar = ''
  form.description_fr = ''
  form.location_ar = ''
  form.location_fr = ''
  form.starts_at = ''
  form.ends_at = ''
  form.status = canPublish.value ? 'published' : 'draft'
  form.show_on_secretariat = true
  form.show_on_home = false
  form.image = null
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await fetchDepartmentEvents(code.value)
    items.value = data.data || []
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

function edit(item) {
  editingId.value = item.id
  form.type = item.type || 'activity'
  form.title_ar = item.title_ar
  form.title_fr = item.title_fr
  form.description_ar = item.description_ar || ''
  form.description_fr = item.description_fr || ''
  form.location_ar = item.location_ar || ''
  form.location_fr = item.location_fr || ''
  form.starts_at = toLocalInput(item.starts_at)
  form.ends_at = toLocalInput(item.ends_at)
  form.status = item.status
  form.show_on_secretariat = item.show_on_secretariat !== false
  form.show_on_home = !!item.show_on_home
  form.image = null
}

async function save() {
  error.value = ''
  try {
    const payload = { ...form }
    if (editingId.value) {
      await updateEvent(code.value, editingId.value, payload)
    } else {
      await createEvent(code.value, payload)
    }
    resetForm()
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

async function publish(id) {
  await publishEvent(code.value, id)
  await load()
}

async function archive(id) {
  await archiveEvent(code.value, id)
  await load()
}

async function remove(id) {
  if (!confirm(t('secretariatAdmin.confirmDelete'))) return
  await deleteEvent(code.value, id)
  await load()
}

onMounted(() => {
  resetForm()
  load()
})
</script>

<template>
  <div class="grid gap-6 lg:grid-cols-[1.1fr_1fr]">
    <div class="space-y-3">
      <h2 class="text-lg font-semibold">{{ t('secretariatAdmin.events') }}</h2>
      <p class="text-sm text-slate-600">{{ t('secretariatAdmin.eventsHint') }}</p>
      <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>
      <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>
      <article
        v-for="item in items"
        :key="item.id"
        class="rounded-lg border border-slate-200 bg-white p-4"
      >
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="text-xs font-medium text-teal-800">{{ typeLabel(item.type) }}</p>
            <p class="mt-1 font-medium">{{ titleOf(item) }}</p>
            <p class="mt-1 text-xs text-slate-500">
              {{ item.status }} · {{ item.starts_at ? toLocalInput(item.starts_at).replace('T', ' ') : '—' }}
            </p>
          </div>
          <div class="flex flex-wrap gap-1">
            <button type="button" class="rounded border px-2 py-1 text-xs" @click="edit(item)">
              {{ t('forms.edit') }}
            </button>
            <button
              v-if="item.status !== 'published' && canPublish"
              type="button"
              class="rounded border px-2 py-1 text-xs"
              @click="publish(item.id)"
            >
              {{ t('secretariatAdmin.publish') }}
            </button>
            <button type="button" class="rounded border px-2 py-1 text-xs" @click="archive(item.id)">
              {{ t('secretariatAdmin.archive') }}
            </button>
            <button type="button" class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700" @click="remove(item.id)">
              {{ t('forms.delete') }}
            </button>
          </div>
        </div>
      </article>
    </div>

    <form class="space-y-3 rounded-xl border border-slate-200 bg-white p-5" @submit.prevent="save">
      <h3 class="font-semibold">
        {{ editingId ? t('secretariatAdmin.editEvent') : t('secretariatAdmin.newEvent') }}
      </h3>
      <select v-model="form.type" class="w-full rounded border px-3 py-2 text-sm">
        <option v-for="type in types" :key="type" :value="type">{{ typeLabel(type) }}</option>
      </select>
      <input v-model="form.title_fr" required :placeholder="t('secretariatAdmin.titleFr')" class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.title_ar" required :placeholder="t('secretariatAdmin.titleAr')" class="w-full rounded border px-3 py-2 text-sm" dir="rtl" />
      <textarea v-model="form.description_fr" rows="4" :placeholder="t('secretariatAdmin.contentFr')" class="w-full rounded border px-3 py-2 text-sm" />
      <textarea v-model="form.description_ar" rows="4" :placeholder="t('secretariatAdmin.contentAr')" class="w-full rounded border px-3 py-2 text-sm" dir="rtl" />
      <input v-model="form.location_fr" :placeholder="t('secretariatAdmin.locationFr')" class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.location_ar" :placeholder="t('secretariatAdmin.locationAr')" class="w-full rounded border px-3 py-2 text-sm" dir="rtl" />
      <label class="block text-xs text-slate-500">
        {{ t('secretariatAdmin.startsAt') }}
        <input v-model="form.starts_at" type="datetime-local" class="mt-1 w-full rounded border px-3 py-2 text-sm" />
      </label>
      <label class="block text-xs text-slate-500">
        {{ t('secretariatAdmin.endsAt') }}
        <input v-model="form.ends_at" type="datetime-local" class="mt-1 w-full rounded border px-3 py-2 text-sm" />
      </label>
      <select v-model="form.status" class="w-full rounded border px-3 py-2 text-sm">
        <option value="draft">draft</option>
        <option value="pending_review">pending_review</option>
        <option v-if="canPublish" value="published">published</option>
        <option value="archived">archived</option>
      </select>
      <label class="flex items-center gap-2 text-sm">
        <input v-model="form.show_on_secretariat" type="checkbox" />
        {{ t('secretariatAdmin.showOnSecretariat') }}
      </label>
      <label class="flex items-center gap-2 text-sm">
        <input v-model="form.show_on_home" type="checkbox" />
        {{ t('secretariatAdmin.showOnHome') }}
      </label>
      <input type="file" accept="image/*" class="w-full text-sm" @change="form.image = $event.target.files?.[0] || null" />
      <div class="flex gap-2">
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white">{{ t('forms.save') }}</button>
        <button type="button" class="rounded border px-4 py-2 text-sm" @click="resetForm">{{ t('forms.cancel') }}</button>
      </div>
    </form>
  </div>
</template>
