<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  deleteSecretariatMessage,
  fetchSecretariatMessages,
  updateSecretariatMessage,
} from '@/services/secretariatMessages'

const route = useRoute()
const { t } = useI18n()
const auth = useAuthStore()
const code = computed(() => route.params.code)

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const items = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const selectedId = ref(null)
const statuses = ['new', 'read', 'replied', 'archived']

const filters = reactive({ search: '', status: '', page: 1 })
const notes = ref('')

const canUpdate = computed(() => auth.hasPermission('inbox.update'))
const canDelete = computed(() => auth.hasPermission('inbox.delete'))
const selected = computed(() => items.value.find((item) => item.id === selectedId.value) || null)

function statusClass(status) {
  if (status === 'replied') return 'bg-emerald-50 text-emerald-800'
  if (status === 'archived') return 'bg-slate-200 text-slate-700'
  if (status === 'read') return 'bg-sky-50 text-sky-800'
  return 'bg-amber-50 text-amber-900'
}

function formatDate(value) {
  if (!value) return ''
  return String(value).slice(0, 16).replace('T', ' ')
}

function mailtoHref(item) {
  const subject = encodeURIComponent(`Re: ${item.subject || ''}`.trim())
  return `mailto:${item.sender_email}?subject=${subject}`
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await fetchSecretariatMessages(code.value, {
      search: filters.search || undefined,
      status: filters.status || undefined,
      page: filters.page,
    })
    items.value = data.data || []
    meta.value = data.meta || { current_page: 1, last_page: 1, total: items.value.length }
    if (selectedId.value && !items.value.some((item) => item.id === selectedId.value)) {
      selectedId.value = null
    }
    notes.value = selected.value?.admin_notes || ''
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

async function openMessage(item) {
  selectedId.value = item.id
  notes.value = item.admin_notes || ''
  if (canUpdate.value && item.status === 'new') {
    await updateSecretariatMessage(code.value, item.id, { status: 'read' })
    await load()
  }
}

async function changeStatus(status) {
  if (!canUpdate.value || !selected.value) return
  await updateSecretariatMessage(code.value, selected.value.id, { status })
  await load()
}

async function saveNotes() {
  if (!canUpdate.value || !selected.value) return
  saving.value = true
  error.value = ''
  try {
    await updateSecretariatMessage(code.value, selected.value.id, { admin_notes: notes.value || null })
    await load()
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || e.message
  } finally {
    saving.value = false
  }
}

async function remove(id) {
  if (!canDelete.value) return
  if (!confirm(t('secretariatAdmin.confirmDelete'))) return
  await deleteSecretariatMessage(code.value, id)
  if (selectedId.value === id) selectedId.value = null
  await load()
}

watch([() => filters.search, () => filters.status], () => {
  filters.page = 1
  selectedId.value = null
  load()
})
watch(() => code.value, () => {
  selectedId.value = null
  filters.page = 1
  load()
})
watch(() => filters.page, load)
onMounted(load)
</script>

<template>
  <div class="grid gap-6 lg:grid-cols-[1.1fr_1fr]">
    <div class="space-y-3">
      <h2 class="text-lg font-semibold">{{ t('secretariatInbox.title') }}</h2>
      <p class="text-sm text-slate-600">{{ t('secretariatInbox.hint') }}</p>
      <div class="grid gap-2 sm:grid-cols-2">
        <input v-model="filters.search" :placeholder="t('secretariatInbox.search')" class="rounded border px-3 py-2 text-sm" />
        <select v-model="filters.status" class="rounded border px-3 py-2 text-sm">
          <option value="">{{ t('secretariatInbox.allStatuses') }}</option>
          <option v-for="status in statuses" :key="status" :value="status">{{ t(`secretariatInbox.statuses.${status}`) }}</option>
        </select>
      </div>
      <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>
      <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>
      <button
        v-for="item in items"
        :key="item.id"
        type="button"
        class="w-full rounded-lg border p-4 text-start"
        :class="item.id === selectedId ? 'border-teal-700 bg-teal-50' : 'border-slate-200 bg-white hover:border-teal-700/40'"
        @click="openMessage(item)"
      >
        <div class="flex flex-wrap items-start justify-between gap-2">
          <div class="min-w-0">
            <p class="text-xs text-slate-500">{{ formatDate(item.created_at) }} · {{ t('secretariatInbox.fromPublic') }}</p>
            <p class="font-medium" :class="item.status === 'new' ? 'text-[var(--rdp-forest)]' : ''">{{ item.sender_name }}</p>
            <p class="mt-1 truncate text-sm text-slate-600">{{ item.subject }}</p>
          </div>
          <span class="rounded px-2 py-1 text-xs font-medium" :class="statusClass(item.status)">
            {{ t(`secretariatInbox.statuses.${item.status}`) }}
          </span>
        </div>
      </button>
      <p v-if="!loading && !items.length" class="text-sm text-slate-500">{{ t('secretariatInbox.empty') }}</p>
      <div v-if="meta.last_page > 1" class="flex items-center gap-2 text-sm">
        <button type="button" class="rounded border px-3 py-1 disabled:opacity-40" :disabled="meta.current_page <= 1" @click="filters.page -= 1">{{ t('admin.prev') }}</button>
        <span>{{ meta.current_page }} / {{ meta.last_page }}</span>
        <button type="button" class="rounded border px-3 py-1 disabled:opacity-40" :disabled="meta.current_page >= meta.last_page" @click="filters.page += 1">{{ t('admin.next') }}</button>
      </div>
    </div>

    <article v-if="selected" class="space-y-3 rounded-xl border border-slate-200 bg-white p-5">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <p class="text-xs text-slate-500">{{ formatDate(selected.created_at) }}</p>
          <h3 class="mt-1 text-lg font-semibold text-[var(--rdp-forest)]">{{ selected.subject }}</h3>
          <p class="mt-1 font-medium">{{ selected.sender_name }}</p>
          <p class="text-sm text-slate-600">
            {{ selected.sender_email }}
            <span v-if="selected.sender_phone"> · {{ selected.sender_phone }}</span>
          </p>
        </div>
        <span class="rounded px-2 py-1 text-xs font-medium" :class="statusClass(selected.status)">
          {{ t(`secretariatInbox.statuses.${selected.status}`) }}
        </span>
      </div>
      <p class="whitespace-pre-line rounded-lg bg-slate-50 p-4 text-sm text-slate-800">{{ selected.body }}</p>
      <div class="flex flex-wrap gap-2">
        <a
          :href="mailtoHref(selected)"
          class="rounded bg-teal-800 px-3 py-1.5 text-xs font-semibold text-white"
        >
          {{ t('secretariatInbox.replyEmail') }}
        </a>
        <select
          v-if="canUpdate"
          class="rounded border px-2 py-1 text-xs"
          :value="selected.status"
          @change="changeStatus($event.target.value)"
        >
          <option v-for="status in statuses" :key="status" :value="status">{{ t(`secretariatInbox.statuses.${status}`) }}</option>
        </select>
        <button
          v-if="canDelete"
          type="button"
          class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700"
          @click="remove(selected.id)"
        >
          {{ t('forms.delete') }}
        </button>
      </div>
      <textarea
        v-model="notes"
        rows="3"
        class="w-full rounded border px-3 py-2 text-sm"
        :placeholder="t('secretariatInbox.adminNotes')"
        :disabled="!canUpdate"
      />
      <button
        v-if="canUpdate"
        type="button"
        class="rounded border px-4 py-2 text-sm disabled:opacity-50"
        :disabled="saving"
        @click="saveNotes"
      >
        {{ t('secretariatInbox.saveNotes') }}
      </button>
    </article>
    <p v-else class="rounded-xl border border-dashed border-slate-300 bg-white p-8 text-sm text-slate-500">
      {{ t('secretariatInbox.select') }}
    </p>
  </div>
</template>
