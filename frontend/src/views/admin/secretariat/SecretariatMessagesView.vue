<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  createSecretariatMessage,
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
const editingId = ref(null)
const statuses = ['new', 'read', 'replied', 'archived']

const filters = reactive({ search: '', status: '' })
const form = reactive(emptyForm())

const canCreate = computed(() => auth.hasPermission('inbox.create'))
const canUpdate = computed(() => auth.hasPermission('inbox.update'))
const canDelete = computed(() => auth.hasPermission('inbox.delete'))
const canManage = computed(() => (editingId.value ? canUpdate.value : canCreate.value))

function emptyForm() {
  return {
    sender_name: '',
    sender_email: '',
    sender_phone: '',
    subject: '',
    body: '',
    status: 'new',
    admin_notes: '',
  }
}

function resetForm() {
  editingId.value = null
  Object.assign(form, emptyForm())
}

function edit(item) {
  editingId.value = item.id
  form.sender_name = item.sender_name || ''
  form.sender_email = item.sender_email || ''
  form.sender_phone = item.sender_phone || ''
  form.subject = item.subject || ''
  form.body = item.body || ''
  form.status = item.status || 'new'
  form.admin_notes = item.admin_notes || ''
}

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

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await fetchSecretariatMessages(code.value, {
      search: filters.search || undefined,
      status: filters.status || undefined,
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
  saving.value = true
  error.value = ''
  try {
    if (editingId.value) {
      await updateSecretariatMessage(code.value, editingId.value, { ...form })
    } else {
      await createSecretariatMessage(code.value, { ...form })
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

async function changeStatus(item, status) {
  if (!canUpdate.value) return
  await updateSecretariatMessage(code.value, item.id, { status })
  await load()
}

async function remove(id) {
  if (!canDelete.value) return
  if (!confirm(t('secretariatAdmin.confirmDelete'))) return
  await deleteSecretariatMessage(code.value, id)
  await load()
}

watch([() => filters.search, () => filters.status], load)
onMounted(load)
</script>

<template>
  <div class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
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
      <article v-for="item in items" :key="item.id" class="rounded-lg border border-slate-200 bg-white p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p class="text-xs text-slate-500">{{ formatDate(item.created_at) }}</p>
            <p class="font-medium">{{ item.sender_name }}</p>
            <p class="mt-1 text-sm text-slate-600">
              {{ item.sender_email }}
              <span v-if="item.sender_phone"> · {{ item.sender_phone }}</span>
            </p>
            <p class="mt-2 font-semibold text-[var(--rdp-forest)]">{{ item.subject }}</p>
            <p class="mt-1 whitespace-pre-line text-sm text-slate-700">{{ item.body }}</p>
          </div>
          <div class="flex flex-col items-end gap-2">
            <span class="rounded px-2 py-1 text-xs font-medium" :class="statusClass(item.status)">
              {{ t(`secretariatInbox.statuses.${item.status}`) }}
            </span>
            <select
              v-if="canUpdate"
              class="rounded border px-2 py-1 text-xs"
              :value="item.status"
              @change="changeStatus(item, $event.target.value)"
            >
              <option v-for="status in statuses" :key="status" :value="status">{{ t(`secretariatInbox.statuses.${status}`) }}</option>
            </select>
            <div class="flex gap-1">
              <button type="button" class="rounded border px-2 py-1 text-xs" @click="edit(item)">{{ t('forms.edit') }}</button>
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
        </div>
      </article>
      <p v-if="!loading && !items.length" class="text-sm text-slate-500">{{ t('secretariatInbox.empty') }}</p>
    </div>

    <form class="space-y-3 rounded-xl border border-slate-200 bg-white p-5" @submit.prevent="save">
      <h3 class="font-semibold">{{ editingId ? t('secretariatInbox.edit') : t('secretariatInbox.new') }}</h3>
      <input v-model="form.sender_name" required :placeholder="t('forms.name')" class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.sender_email" required type="email" :placeholder="t('forms.email')" class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.sender_phone" :placeholder="t('forms.phone')" class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.subject" required :placeholder="t('forms.subject')" class="w-full rounded border px-3 py-2 text-sm" />
      <textarea v-model="form.body" required rows="5" :placeholder="t('forms.message')" class="w-full rounded border px-3 py-2 text-sm" />
      <select v-model="form.status" class="w-full rounded border px-3 py-2 text-sm">
        <option v-for="status in statuses" :key="status" :value="status">{{ t(`secretariatInbox.statuses.${status}`) }}</option>
      </select>
      <textarea v-model="form.admin_notes" rows="3" :placeholder="t('secretariatInbox.adminNotes')" class="w-full rounded border px-3 py-2 text-sm" />
      <div class="flex gap-2">
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white disabled:opacity-50" :disabled="!canManage || saving">
          {{ t('forms.save') }}
        </button>
        <button type="button" class="rounded border px-4 py-2 text-sm" @click="resetForm">{{ t('forms.cancel') }}</button>
      </div>
    </form>
  </div>
</template>
