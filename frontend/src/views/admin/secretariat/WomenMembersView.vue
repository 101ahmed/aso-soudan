<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  createWomenMember,
  deleteWomenMember,
  fetchWomenMembers,
  updateWomenMember,
} from '@/services/womenMembers'

const route = useRoute()
const { t } = useI18n()
const auth = useAuthStore()
const code = computed(() => route.params.code)

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const items = ref([])
const editingId = ref(null)

const genders = ['female', 'male']
const maritalStatuses = ['single', 'married', 'divorced', 'widowed']

const filters = reactive({
  search: '',
  gender: '',
  marital_status: '',
  page: 1,
})

const form = reactive(emptyForm())

const canCreate = computed(() => auth.hasPermission('women_member.create'))
const canUpdate = computed(() => auth.hasPermission('women_member.update'))
const canDelete = computed(() => auth.hasPermission('women_member.delete'))
const canManage = computed(() => (editingId.value ? canUpdate.value : canCreate.value))

function emptyForm() {
  return {
    full_name: '',
    gender: 'female',
    residence: '',
    marital_status: 'married',
    children_count: 0,
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
  form.gender = item.gender || 'female'
  form.residence = item.residence || ''
  form.marital_status = item.marital_status || ''
  form.children_count = item.children_count ?? 0
  form.notes = item.notes || ''
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await fetchWomenMembers(code.value, {
      search: filters.search || undefined,
      gender: filters.gender || undefined,
      marital_status: filters.marital_status || undefined,
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
      children_count: form.children_count === '' || form.children_count === null
        ? 0
        : Number(form.children_count),
      marital_status: form.marital_status || null,
    }
    if (editingId.value) {
      await updateWomenMember(code.value, editingId.value, payload)
    } else {
      await createWomenMember(code.value, payload)
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
  if (!canDelete.value) return
  if (!confirm(t('secretariatAdmin.confirmDelete'))) return
  await deleteWomenMember(code.value, id)
  await load()
}

watch([() => filters.search, () => filters.gender, () => filters.marital_status], () => {
  filters.page = 1
  load()
})

onMounted(load)
</script>

<template>
  <div class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
    <div class="space-y-3">
      <h2 class="text-lg font-semibold">{{ t('womenMembers.adminTitle') }}</h2>
      <p class="text-sm text-slate-600">{{ t('womenMembers.adminHint') }}</p>
      <div class="grid gap-2 sm:grid-cols-3">
        <input v-model="filters.search" :placeholder="t('womenMembers.search')" class="rounded border px-3 py-2 text-sm" />
        <select v-model="filters.gender" class="rounded border px-3 py-2 text-sm">
          <option value="">{{ t('womenMembers.allGenders') }}</option>
          <option v-for="g in genders" :key="g" :value="g">{{ t(`womenMembers.genders.${g}`) }}</option>
        </select>
        <select v-model="filters.marital_status" class="rounded border px-3 py-2 text-sm">
          <option value="">{{ t('womenMembers.allMarital') }}</option>
          <option v-for="s in maritalStatuses" :key="s" :value="s">{{ t(`womenMembers.marital.${s}`) }}</option>
        </select>
      </div>
      <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>
      <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>
      <p v-if="!loading && !items.length" class="text-sm text-slate-500">{{ t('womenMembers.empty') }}</p>
      <article v-for="item in items" :key="item.id" class="rounded-lg border border-slate-200 bg-white p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p class="font-medium">{{ item.full_name }}</p>
            <p class="mt-1 text-sm text-slate-600">
              {{ t(`womenMembers.genders.${item.gender}`) }}
              <span v-if="item.residence"> · {{ item.residence }}</span>
            </p>
            <p class="mt-1 text-sm text-slate-700">
              <span v-if="item.marital_status">{{ t(`womenMembers.marital.${item.marital_status}`) }}</span>
              <span v-if="item.marital_status"> · </span>
              {{ t('womenMembers.childrenCount') }}: {{ item.children_count ?? 0 }}
            </p>
            <p v-if="item.notes" class="mt-2 whitespace-pre-line text-sm text-slate-600">{{ item.notes }}</p>
          </div>
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
      </article>
    </div>

    <form class="space-y-3 rounded-xl border border-slate-200 bg-white p-5" @submit.prevent="save">
      <h3 class="font-semibold">{{ editingId ? t('womenMembers.edit') : t('womenMembers.new') }}</h3>
      <label class="block text-sm">
        <span class="mb-1 block text-slate-600">{{ t('womenMembers.fullName') }}</span>
        <input v-model="form.full_name" required class="w-full rounded border px-3 py-2 text-sm" />
      </label>
      <label class="block text-sm">
        <span class="mb-1 block text-slate-600">{{ t('womenMembers.gender') }}</span>
        <select v-model="form.gender" required class="w-full rounded border px-3 py-2 text-sm">
          <option v-for="g in genders" :key="g" :value="g">{{ t(`womenMembers.genders.${g}`) }}</option>
        </select>
      </label>
      <label class="block text-sm">
        <span class="mb-1 block text-slate-600">{{ t('womenMembers.residence') }}</span>
        <input v-model="form.residence" class="w-full rounded border px-3 py-2 text-sm" />
      </label>
      <label class="block text-sm">
        <span class="mb-1 block text-slate-600">{{ t('womenMembers.maritalStatus') }}</span>
        <select v-model="form.marital_status" class="w-full rounded border px-3 py-2 text-sm">
          <option value="">{{ t('womenMembers.maritalUnset') }}</option>
          <option v-for="s in maritalStatuses" :key="s" :value="s">{{ t(`womenMembers.marital.${s}`) }}</option>
        </select>
      </label>
      <label class="block text-sm">
        <span class="mb-1 block text-slate-600">{{ t('womenMembers.childrenCount') }}</span>
        <input v-model="form.children_count" type="number" min="0" max="30" class="w-full rounded border px-3 py-2 text-sm" />
      </label>
      <label class="block text-sm">
        <span class="mb-1 block text-slate-600">{{ t('womenMembers.notes') }}</span>
        <textarea v-model="form.notes" rows="3" class="w-full rounded border px-3 py-2 text-sm" />
      </label>
      <div class="flex gap-2">
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white disabled:opacity-50" :disabled="!canManage || saving">
          {{ t('forms.save') }}
        </button>
        <button type="button" class="rounded border px-4 py-2 text-sm" @click="resetForm">{{ t('forms.cancel') }}</button>
      </div>
    </form>
  </div>
</template>
