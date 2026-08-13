<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  createTeacher,
  deleteTeacher,
  fetchSubjects,
  fetchTeachers,
  updateTeacher,
} from '@/services/academic'

const { t, locale } = useI18n()
const auth = useAuthStore()

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const items = ref([])
const meta = ref(null)
const subjects = ref([])
const editingId = ref(null)

const filters = reactive({ search: '', status: '', page: 1 })
const form = reactive({
  first_name: '',
  last_name: '',
  email: '',
  phone: '',
  locale: 'ar',
  status: 'active',
  hired_on: '',
  notes: '',
  password: '',
  password_confirmation: '',
  subject_ids: [],
})

const canView = computed(() => auth.hasPermission('teacher.view'))
const canCreate = computed(() => auth.hasPermission('teacher.create'))
const canUpdate = computed(() => auth.hasPermission('teacher.update') || auth.hasPermission('teacher.create'))
const canManage = computed(() => (editingId.value ? canUpdate.value : canCreate.value))

function subjectName(item) {
  return locale.value === 'ar' ? item.name_ar : item.name_fr
}

function resetForm() {
  editingId.value = null
  Object.assign(form, {
    first_name: '', last_name: '', email: '', phone: '', locale: 'ar', status: 'active',
    hired_on: '', notes: '', password: '', password_confirmation: '', subject_ids: [],
  })
}

function toggleSubject(id) {
  const value = Number(id)
  if (form.subject_ids.includes(value)) form.subject_ids = form.subject_ids.filter((item) => item !== value)
  else form.subject_ids.push(value)
}

async function load() {
  if (!canView.value) return
  loading.value = true
  error.value = ''
  try {
    const response = await fetchTeachers({
      page: filters.page,
      search: filters.search || undefined,
      status: filters.status || undefined,
    })
    items.value = response.data || []
    meta.value = response.meta || null
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

function edit(item) {
  editingId.value = item.id
  Object.assign(form, {
    first_name: item.first_name || '',
    last_name: item.last_name || '',
    email: item.email || '',
    phone: item.phone || '',
    locale: item.locale || 'ar',
    status: item.status || 'active',
    hired_on: item.hired_on || '',
    notes: item.notes || '',
    password: '',
    password_confirmation: '',
    subject_ids: (item.subjects || []).map((subject) => subject.id),
  })
}

async function save() {
  if (!canManage.value) return
  saving.value = true
  error.value = ''
  try {
    const payload = {
      first_name: form.first_name,
      last_name: form.last_name,
      email: form.email,
      phone: form.phone || null,
      locale: form.locale,
      status: form.status,
      hired_on: form.hired_on || null,
      notes: form.notes || null,
      subject_ids: form.subject_ids,
    }
    if (form.password) {
      payload.password = form.password
      payload.password_confirmation = form.password_confirmation
    }
    if (editingId.value) await updateTeacher(editingId.value, payload)
    else {
      if (!form.password) {
        error.value = t('academicTeachers.passwordRequired')
        return
      }
      payload.password = form.password
      payload.password_confirmation = form.password_confirmation
      await createTeacher(payload)
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

async function remove(item) {
  if (!canUpdate.value) return
  if (!confirm(t('academicTeachers.confirmDelete', { name: item.full_name }))) return
  try {
    await deleteTeacher(item.id)
    if (editingId.value === item.id) resetForm()
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

onMounted(async () => {
  try { subjects.value = await fetchSubjects() } catch { subjects.value = [] }
  await load()
})
</script>

<template>
  <div class="space-y-5">
    <div>
      <h2 class="text-lg font-semibold text-[var(--rdp-forest)]">{{ t('academicTeachers.title') }}</h2>
      <p class="mt-1 text-sm text-slate-600">{{ t('academicTeachers.subtitle') }}</p>
    </div>
    <p v-if="!canView" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ t('academicTeachers.forbidden') }}</p>
    <template v-else>
      <div class="grid gap-3 rounded-xl border bg-white p-4 md:grid-cols-3">
        <input v-model="filters.search" type="search" :placeholder="t('academicTeachers.search')" class="rounded-md border px-3 py-2 text-sm" @keyup.enter="filters.page = 1; load()" />
        <select v-model="filters.status" class="rounded-md border px-3 py-2 text-sm">
          <option value="">{{ t('academicTeachers.allStatuses') }}</option>
          <option value="active">{{ t('academicTeachers.statuses.active') }}</option>
          <option value="inactive">{{ t('academicTeachers.statuses.inactive') }}</option>
          <option value="suspended">{{ t('academicTeachers.statuses.suspended') }}</option>
        </select>
        <button type="button" class="rounded-md border px-3 py-2 text-sm" @click="filters.page = 1; load()">{{ t('academicTeachers.filter') }}</button>
      </div>
      <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
      <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <div class="overflow-x-auto rounded-xl border bg-white">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-start text-xs text-slate-500">
              <tr>
                <th class="px-4 py-3 font-medium">{{ t('academicTeachers.name') }}</th>
                <th class="px-4 py-3 font-medium">{{ t('forms.email') }}</th>
                <th class="px-4 py-3 font-medium">{{ t('academicTeachers.subjects') }}</th>
                <th class="px-4 py-3 font-medium">{{ t('academicTeachers.status') }}</th>
                <th class="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!loading && !items.length"><td colspan="5" class="px-4 py-6 text-center text-slate-500">{{ t('academicTeachers.empty') }}</td></tr>
              <tr v-for="item in items" :key="item.id" class="border-t">
                <td class="px-4 py-3 font-medium">{{ item.full_name }}</td>
                <td class="px-4 py-3">{{ item.email || '—' }}</td>
                <td class="px-4 py-3">{{ (item.subjects || []).map(subjectName).join(' · ') || '—' }}</td>
                <td class="px-4 py-3">{{ t(`academicTeachers.statuses.${item.status}`) }}</td>
                <td class="px-4 py-3 flex gap-2">
                  <button v-if="canUpdate" type="button" class="text-teal-800 hover:underline" @click="edit(item)">{{ t('forms.edit') }}</button>
                  <button v-if="canUpdate" type="button" class="text-rose-700 hover:underline" @click="remove(item)">{{ t('forms.delete') }}</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <form v-if="canCreate || canUpdate" class="space-y-3 rounded-xl border bg-white p-5" @submit.prevent="save">
          <h3 class="font-semibold">{{ editingId ? t('academicTeachers.editTeacher') : t('academicTeachers.newTeacher') }}</h3>
          <p class="text-xs text-slate-500">{{ t('academicTeachers.accountHint') }}</p>
          <div class="grid grid-cols-2 gap-2">
            <input v-model="form.first_name" required class="rounded border px-3 py-2 text-sm" :placeholder="t('forms.firstName')" />
            <input v-model="form.last_name" required class="rounded border px-3 py-2 text-sm" :placeholder="t('forms.lastName')" />
          </div>
          <input v-model="form.email" type="email" required class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('forms.email')" />
          <input v-model="form.phone" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('forms.phone')" />
          <input v-model="form.hired_on" type="date" class="w-full rounded border px-3 py-2 text-sm" />
          <fieldset class="rounded-lg border p-3">
            <legend class="px-1 text-xs">{{ t('academicTeachers.subjects') }}</legend>
            <label v-for="subject in subjects" :key="subject.id" class="flex items-center gap-2 text-sm">
              <input type="checkbox" :checked="form.subject_ids.includes(subject.id)" @change="toggleSubject(subject.id)" />
              {{ subjectName(subject) }}
            </label>
          </fieldset>
          <input v-model="form.password" type="password" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('academicTeachers.password')" />
          <input v-model="form.password_confirmation" type="password" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('academicTeachers.passwordConfirm')" />
          <div class="flex gap-2">
            <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white" :disabled="saving">{{ t('forms.save') }}</button>
            <button v-if="editingId" type="button" class="rounded border px-4 py-2 text-sm" @click="resetForm">{{ t('forms.cancel') }}</button>
          </div>
        </form>
      </div>
    </template>
  </div>
</template>
