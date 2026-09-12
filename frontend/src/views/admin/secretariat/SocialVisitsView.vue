<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  createSocialVisit,
  deleteSocialVisit,
  fetchSocialVisits,
  updateSocialVisit,
} from '@/services/socialVisits'

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
  visit_type: '',
  status: '',
  page: 1,
})

const visitTypes = ['home', 'hospital', 'condolence', 'congratulations', 'ramadan', 'follow_up', 'new_family', 'other']
const statuses = ['planned', 'completed', 'cancelled']

const form = reactive(emptyForm())

const canCreate = computed(() => auth.hasPermission('help.create'))
const canUpdate = computed(() => auth.hasPermission('help.update'))
const canDelete = computed(() => auth.hasPermission('help.delete'))
const canManage = computed(() => (editingId.value ? canUpdate.value : canCreate.value))

function emptyForm() {
  return {
    full_name: '',
    phone: '',
    place: '',
    visit_type: 'home',
    reason: '',
    visited_on: '',
    visited_at: '',
    visitors: '',
    status: 'planned',
    notes: '',
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
  form.place = item.place || ''
  form.visit_type = item.visit_type
  form.reason = item.reason || ''
  form.visited_on = item.visited_on || ''
  form.visited_at = item.visited_at || ''
  form.visitors = item.visitors || ''
  form.status = item.status || 'planned'
  form.notes = item.notes || ''
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await fetchSocialVisits(code.value, {
      search: filters.search || undefined,
      visit_type: filters.visit_type || undefined,
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

function payload() {
  return {
    ...form,
    visited_at: form.visited_at || null,
    phone: form.phone || null,
    visitors: form.visitors || null,
    notes: form.notes || null,
  }
}

async function save() {
  if (!canManage.value) return
  saving.value = true
  error.value = ''
  try {
    if (editingId.value) {
      await updateSocialVisit(code.value, editingId.value, payload())
    } else {
      await createSocialVisit(code.value, payload())
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
  await updateSocialVisit(code.value, item.id, { status })
  await load()
}

async function remove(id) {
  if (!canDelete.value) return
  if (!confirm(t('secretariatAdmin.confirmDelete'))) return
  await deleteSocialVisit(code.value, id)
  await load()
}

function statusClass(status) {
  if (status === 'completed') return 'bg-emerald-50 text-emerald-800'
  if (status === 'cancelled') return 'bg-rose-50 text-rose-800'
  return 'bg-amber-50 text-amber-900'
}

watch([() => filters.search, () => filters.visit_type, () => filters.status], () => {
  filters.page = 1
  load()
})

onMounted(load)
</script>

<template>
  <div class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
    <div class="space-y-3">
      <h2 class="text-lg font-semibold">{{ t('socialVisits.adminTitle') }}</h2>
      <p class="text-sm text-slate-600">{{ t('socialVisits.adminHint') }}</p>
      <div class="grid gap-2 sm:grid-cols-3">
        <input v-model="filters.search" :placeholder="t('socialVisits.search')" class="rounded border px-3 py-2 text-sm" />
        <select v-model="filters.visit_type" class="rounded border px-3 py-2 text-sm">
          <option value="">{{ t('socialVisits.allTypes') }}</option>
          <option v-for="type in visitTypes" :key="type" :value="type">{{ t(`socialVisits.types.${type}`) }}</option>
        </select>
        <select v-model="filters.status" class="rounded border px-3 py-2 text-sm">
          <option value="">{{ t('socialVisits.allStatuses') }}</option>
          <option v-for="status in statuses" :key="status" :value="status">{{ t(`socialVisits.statuses.${status}`) }}</option>
        </select>
      </div>
      <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>
      <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>
      <p v-else-if="!loading && !items.length" class="text-sm text-slate-500">{{ t('socialVisits.empty') }}</p>
      <article v-for="item in items" :key="item.id" class="rounded-lg border border-slate-200 bg-white p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p class="font-medium">{{ item.full_name }}</p>
            <p class="mt-1 text-sm text-slate-600">
              {{ item.visited_on }}
              <span v-if="item.visited_at"> · {{ item.visited_at }}</span>
              <span v-if="item.place"> · {{ item.place }}</span>
            </p>
            <p class="mt-1 text-sm">{{ t(`socialVisits.types.${item.visit_type}`) }}</p>
            <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ item.reason }}</p>
            <p v-if="item.visitors" class="mt-1 text-xs text-slate-500">{{ t('socialVisits.visitors') }}: {{ item.visitors }}</p>
            <p v-if="item.phone" class="mt-1 text-xs text-slate-500">{{ item.phone }}</p>
          </div>
          <div class="flex flex-col items-end gap-2">
            <span class="rounded px-2 py-1 text-xs font-medium" :class="statusClass(item.status)">
              {{ t(`socialVisits.statuses.${item.status}`) }}
            </span>
            <select
              v-if="canUpdate"
              class="rounded border px-2 py-1 text-xs"
              :value="item.status"
              @change="changeStatus(item, $event.target.value)"
            >
              <option v-for="status in statuses" :key="status" :value="status">{{ t(`socialVisits.statuses.${status}`) }}</option>
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
      <h3 class="font-semibold">{{ editingId ? t('socialVisits.edit') : t('socialVisits.new') }}</h3>
      <input v-model="form.full_name" required :placeholder="t('socialVisits.fullName')" class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.phone" :placeholder="t('forms.phone')" class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.place" required :placeholder="t('socialVisits.place')" class="w-full rounded border px-3 py-2 text-sm" />
      <select v-model="form.visit_type" required class="w-full rounded border px-3 py-2 text-sm">
        <option v-for="type in visitTypes" :key="type" :value="type">{{ t(`socialVisits.types.${type}`) }}</option>
      </select>
      <textarea v-model="form.reason" required rows="3" :placeholder="t('socialVisits.reason')" class="w-full rounded border px-3 py-2 text-sm" />
      <div class="grid gap-2 sm:grid-cols-2">
        <label class="text-xs text-slate-500">
          {{ t('socialVisits.date') }}
          <input v-model="form.visited_on" type="date" required class="mt-1 w-full rounded border px-3 py-2 text-sm text-slate-800" />
        </label>
        <label class="text-xs text-slate-500">
          {{ t('socialVisits.time') }}
          <input v-model="form.visited_at" type="time" class="mt-1 w-full rounded border px-3 py-2 text-sm text-slate-800" />
        </label>
      </div>
      <input v-model="form.visitors" :placeholder="t('socialVisits.visitors')" class="w-full rounded border px-3 py-2 text-sm" />
      <select v-model="form.status" class="w-full rounded border px-3 py-2 text-sm">
        <option v-for="status in statuses" :key="status" :value="status">{{ t(`socialVisits.statuses.${status}`) }}</option>
      </select>
      <textarea v-model="form.notes" rows="3" :placeholder="t('socialVisits.notes')" class="w-full rounded border px-3 py-2 text-sm" />
      <div class="flex gap-2">
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white disabled:opacity-50" :disabled="!canManage || saving">
          {{ t('forms.save') }}
        </button>
        <button type="button" class="rounded border px-4 py-2 text-sm" @click="resetForm">{{ t('forms.cancel') }}</button>
      </div>
    </form>
  </div>
</template>
