<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  createHelpRequest,
  deleteHelpRequest,
  fetchHelpRequests,
  updateHelpRequest,
} from '@/services/helpRequests'

const route = useRoute()
const { t } = useI18n()
const auth = useAuthStore()
const code = computed(() => route.params.code)

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const items = ref([])
const editingId = ref(null)

const filters = reactive({
  search: '',
  help_type: '',
  status: '',
  page: 1,
})

const helpTypes = ['financial', 'food', 'housing', 'admin_papers', 'health', 'family', 'ramadan', 'emergency', 'other']
const statuses = ['pending', 'reviewing', 'approved', 'in_progress', 'completed', 'rejected']

const form = reactive(emptyForm())

const canCreate = computed(() => auth.hasPermission('help.create'))
const canUpdate = computed(() => auth.hasPermission('help.update'))
const canDelete = computed(() => auth.hasPermission('help.delete'))
const canManage = computed(() => (editingId.value ? canUpdate.value : canCreate.value))

function emptyForm() {
  return {
    full_name: '',
    phone: '',
    email: '',
    city: '',
    help_type: 'financial',
    details: '',
    family_size: '',
    status: 'pending',
    admin_notes: '',
  }
}

function resetForm() {
  editingId.value = null
  Object.assign(form, emptyForm())
}

function edit(item) {
  editingId.value = item.id
  form.full_name = item.full_name
  form.phone = item.phone || ''
  form.email = item.email || ''
  form.city = item.city || ''
  form.help_type = item.help_type
  form.details = item.details || ''
  form.family_size = item.family_size || ''
  form.status = item.status
  form.admin_notes = item.admin_notes || ''
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await fetchHelpRequests(code.value, {
      search: filters.search || undefined,
      help_type: filters.help_type || undefined,
      status: filters.status || undefined,
      page: filters.page,
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
    const payload = {
      ...form,
      family_size: form.family_size ? Number(form.family_size) : null,
    }
    if (editingId.value) {
      await updateHelpRequest(code.value, editingId.value, payload)
    } else {
      await createHelpRequest(code.value, payload)
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
  await updateHelpRequest(code.value, item.id, { status })
  await load()
}

async function remove(id) {
  if (!canDelete.value) return
  if (!confirm(t('secretariatAdmin.confirmDelete'))) return
  await deleteHelpRequest(code.value, id)
  await load()
}

function statusClass(status) {
  if (status === 'completed' || status === 'approved') return 'bg-emerald-50 text-emerald-800'
  if (status === 'rejected') return 'bg-rose-50 text-rose-800'
  if (status === 'in_progress' || status === 'reviewing') return 'bg-amber-50 text-amber-900'
  return 'bg-slate-100 text-slate-700'
}

watch([() => filters.search, () => filters.help_type, () => filters.status], () => {
  filters.page = 1
  load()
})

onMounted(load)
</script>

<template>
  <div class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
    <div class="space-y-3">
      <h2 class="text-lg font-semibold">{{ t('socialHelp.adminTitle') }}</h2>
      <p class="text-sm text-slate-600">{{ t('socialHelp.adminHint') }}</p>
      <div class="grid gap-2 sm:grid-cols-3">
        <input v-model="filters.search" :placeholder="t('socialHelp.search')" class="rounded border px-3 py-2 text-sm" />
        <select v-model="filters.help_type" class="rounded border px-3 py-2 text-sm">
          <option value="">{{ t('socialHelp.allTypes') }}</option>
          <option v-for="type in helpTypes" :key="type" :value="type">{{ t(`socialHelp.types.${type}`) }}</option>
        </select>
        <select v-model="filters.status" class="rounded border px-3 py-2 text-sm">
          <option value="">{{ t('socialHelp.allStatuses') }}</option>
          <option v-for="status in statuses" :key="status" :value="status">{{ t(`socialHelp.statuses.${status}`) }}</option>
        </select>
      </div>
      <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>
      <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>
      <article v-for="item in items" :key="item.id" class="rounded-lg border border-slate-200 bg-white p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p class="text-xs text-slate-500">{{ item.reference }}</p>
            <p class="font-medium">{{ item.full_name }}</p>
            <p class="mt-1 text-sm text-slate-600">{{ item.phone }} <span v-if="item.city">· {{ item.city }}</span></p>
            <p class="mt-1 text-sm">
              {{ t(`socialHelp.types.${item.help_type}`) }}
            </p>
            <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ item.details }}</p>
          </div>
          <div class="flex flex-col items-end gap-2">
            <span class="rounded px-2 py-1 text-xs font-medium" :class="statusClass(item.status)">
              {{ t(`socialHelp.statuses.${item.status}`) }}
            </span>
            <select
              v-if="canUpdate"
              class="rounded border px-2 py-1 text-xs"
              :value="item.status"
              @change="changeStatus(item, $event.target.value)"
            >
              <option v-for="status in statuses" :key="status" :value="status">{{ t(`socialHelp.statuses.${status}`) }}</option>
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
    </div>

    <form class="space-y-3 rounded-xl border border-slate-200 bg-white p-5" @submit.prevent="save">
      <h3 class="font-semibold">{{ editingId ? t('socialHelp.edit') : t('socialHelp.new') }}</h3>
      <input v-model="form.full_name" required :placeholder="t('forms.name')" class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.phone" required :placeholder="t('forms.phone')" class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.email" type="email" :placeholder="t('forms.email')" class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.city" :placeholder="t('forms.city')" class="w-full rounded border px-3 py-2 text-sm" />
      <select v-model="form.help_type" required class="w-full rounded border px-3 py-2 text-sm">
        <option v-for="type in helpTypes" :key="type" :value="type">{{ t(`socialHelp.types.${type}`) }}</option>
      </select>
      <input v-model="form.family_size" type="number" min="1" max="30" :placeholder="t('socialHelp.familySize')" class="w-full rounded border px-3 py-2 text-sm" />
      <textarea v-model="form.details" required rows="4" :placeholder="t('socialHelp.details')" class="w-full rounded border px-3 py-2 text-sm" />
      <select v-model="form.status" class="w-full rounded border px-3 py-2 text-sm">
        <option v-for="status in statuses" :key="status" :value="status">{{ t(`socialHelp.statuses.${status}`) }}</option>
      </select>
      <textarea v-model="form.admin_notes" rows="3" :placeholder="t('socialHelp.adminNotes')" class="w-full rounded border px-3 py-2 text-sm" />
      <div class="flex gap-2">
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white disabled:opacity-50" :disabled="!canManage || saving">
          {{ t('forms.save') }}
        </button>
        <button type="button" class="rounded border px-4 py-2 text-sm" @click="resetForm">{{ t('forms.cancel') }}</button>
      </div>
    </form>
  </div>
</template>
