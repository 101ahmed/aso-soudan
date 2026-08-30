<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  createTeacher,
  deleteTeacher,
  fetchLevels,
  fetchSubjects,
  fetchTeachers,
  updateTeacher,
} from '@/services/academic'
import { pickName } from '@/utils/localized'

const { t, locale } = useI18n()
const auth = useAuthStore()

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const items = ref([])
const subjects = ref([])
const levels = ref([])
const editingId = ref(null)
const groupBy = ref('subject')

const filters = reactive({ search: '', status: '', subject_id: '', level_id: '', page: 1 })
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
  level_ids: [],
})

const canView = computed(() => auth.hasPermission('teacher.view'))
const canCreate = computed(() => auth.hasPermission('teacher.create'))
const canUpdate = computed(() => auth.hasPermission('teacher.update') || auth.hasPermission('teacher.create'))
const canManage = computed(() => (editingId.value ? canUpdate.value : canCreate.value))

function label(item) {
  return pickName(item, locale.value)
}

function resetForm() {
  editingId.value = null
  Object.assign(form, {
    first_name: '', last_name: '', email: '', phone: '', locale: 'ar', status: 'active',
    hired_on: '', notes: '', password: '', password_confirmation: '',
    subject_ids: [],
    level_ids: levels.value.map((level) => level.id),
  })
}

function toggleId(listName, id) {
  const value = Number(id)
  if (form[listName].includes(value)) form[listName] = form[listName].filter((item) => item !== value)
  else form[listName].push(value)
}

function names(list) {
  return (list || []).map(label).join(' · ') || '—'
}

function teachersForSubject(subjectId) {
  return items.value.filter((item) => (item.subjects || []).some((subject) => String(subject.id) === String(subjectId)))
}

function teachersForLevel(levelId) {
  return items.value.filter((item) => (item.levels || []).some((level) => String(level.id) === String(levelId)))
}

const unassigned = computed(() => items.value.filter((item) => {
  if (groupBy.value === 'level') return !(item.levels || []).length
  return !(item.subjects || []).length
}))

function selectSubject(id) {
  filters.subject_id = id
  filters.page = 1
  load()
}

function selectLevel(id) {
  filters.level_id = id
  filters.page = 1
  load()
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
      subject_id: filters.subject_id || undefined,
      level_id: filters.level_id || undefined,
      per_page: 100,
    })
    items.value = response.data || []
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
    level_ids: (item.levels || []).map((level) => level.id),
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
      level_ids: form.level_ids,
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
  try { levels.value = await fetchLevels() } catch { levels.value = [] }
  resetForm()
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

      <div class="flex flex-wrap gap-2">
        <button
          type="button"
          class="rounded-full px-3 py-1.5 text-sm"
          :class="groupBy === 'subject' ? 'bg-teal-800 text-white' : 'border bg-white text-slate-700'"
          @click="groupBy = 'subject'; filters.level_id = ''; load()"
        >
          {{ t('academicTeachers.bySubject') }}
        </button>
        <button
          type="button"
          class="rounded-full px-3 py-1.5 text-sm"
          :class="groupBy === 'level' ? 'bg-teal-800 text-white' : 'border bg-white text-slate-700'"
          @click="groupBy = 'level'; filters.subject_id = ''; load()"
        >
          {{ t('academicTeachers.byLevel') }}
        </button>
      </div>

      <div v-if="groupBy === 'subject'" class="flex flex-wrap gap-2">
        <button
          type="button"
          class="rounded-full px-3 py-1.5 text-sm"
          :class="!filters.subject_id ? 'bg-teal-800 text-white' : 'border bg-white text-slate-700'"
          @click="selectSubject('')"
        >
          {{ t('academicTeachers.allSubjects') }}
        </button>
        <button
          v-for="subject in subjects"
          :key="subject.id"
          type="button"
          class="rounded-full px-3 py-1.5 text-sm"
          :class="String(filters.subject_id) === String(subject.id) ? 'bg-teal-800 text-white' : 'border bg-white text-slate-700'"
          @click="selectSubject(String(subject.id))"
        >
          {{ label(subject) }}
        </button>
      </div>
      <div v-else class="flex flex-wrap gap-2">
        <button
          type="button"
          class="rounded-full px-3 py-1.5 text-sm"
          :class="!filters.level_id ? 'bg-teal-800 text-white' : 'border bg-white text-slate-700'"
          @click="selectLevel('')"
        >
          {{ t('academicTeachers.allLevels') }}
        </button>
        <button
          v-for="level in levels"
          :key="level.id"
          type="button"
          class="rounded-full px-3 py-1.5 text-sm"
          :class="String(filters.level_id) === String(level.id) ? 'bg-teal-800 text-white' : 'border bg-white text-slate-700'"
          @click="selectLevel(String(level.id))"
        >
          {{ label(level) }}
        </button>
      </div>

      <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
      <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <div class="space-y-4">
          <template v-if="groupBy === 'subject' && !filters.subject_id">
            <div v-for="subject in subjects" :key="subject.id" class="overflow-x-auto rounded-xl border bg-white">
              <div class="flex items-center justify-between border-b px-4 py-3">
                <h3 class="font-semibold text-[var(--rdp-forest)]">{{ label(subject) }}</h3>
                <p class="text-xs text-slate-500">{{ teachersForSubject(subject.id).length }} {{ t('academicTeachers.teachersCount') }}</p>
              </div>
              <table class="min-w-full text-sm">
                <tbody>
                  <tr v-if="!teachersForSubject(subject.id).length">
                    <td class="px-4 py-6 text-center text-slate-500">{{ t('academicTeachers.emptyGroup') }}</td>
                  </tr>
                  <tr v-for="item in teachersForSubject(subject.id)" :key="item.id" class="border-t">
                    <td class="px-4 py-3 font-medium">{{ item.full_name }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ names(item.levels) }}</td>
                    <td class="px-4 py-3 flex gap-2">
                      <button v-if="canUpdate" type="button" class="text-teal-800 hover:underline" @click="edit(item)">{{ t('forms.edit') }}</button>
                      <button v-if="canUpdate" type="button" class="text-rose-700 hover:underline" @click="remove(item)">{{ t('forms.delete') }}</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>
          <template v-else-if="groupBy === 'level' && !filters.level_id">
            <div v-for="level in levels" :key="level.id" class="overflow-x-auto rounded-xl border bg-white">
              <div class="flex items-center justify-between border-b px-4 py-3">
                <h3 class="font-semibold text-[var(--rdp-forest)]">{{ label(level) }}</h3>
                <p class="text-xs text-slate-500">{{ teachersForLevel(level.id).length }} {{ t('academicTeachers.teachersCount') }}</p>
              </div>
              <table class="min-w-full text-sm">
                <tbody>
                  <tr v-if="!teachersForLevel(level.id).length">
                    <td class="px-4 py-6 text-center text-slate-500">{{ t('academicTeachers.emptyGroup') }}</td>
                  </tr>
                  <tr v-for="item in teachersForLevel(level.id)" :key="item.id" class="border-t">
                    <td class="px-4 py-3 font-medium">{{ item.full_name }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ names(item.subjects) }}</td>
                    <td class="px-4 py-3 flex gap-2">
                      <button v-if="canUpdate" type="button" class="text-teal-800 hover:underline" @click="edit(item)">{{ t('forms.edit') }}</button>
                      <button v-if="canUpdate" type="button" class="text-rose-700 hover:underline" @click="remove(item)">{{ t('forms.delete') }}</button>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </template>
          <div v-else class="overflow-x-auto rounded-xl border bg-white">
            <table class="min-w-full text-sm">
              <thead class="bg-slate-50 text-start text-xs text-slate-500">
                <tr>
                  <th class="px-4 py-3 font-medium">{{ t('academicTeachers.name') }}</th>
                  <th class="px-4 py-3 font-medium">{{ t('academicTeachers.subjects') }}</th>
                  <th class="px-4 py-3 font-medium">{{ t('academicTeachers.levels') }}</th>
                  <th class="px-4 py-3 font-medium">{{ t('academicTeachers.status') }}</th>
                  <th class="px-4 py-3"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-if="!loading && !items.length">
                  <td colspan="5" class="px-4 py-6 text-center text-slate-500">{{ t('academicTeachers.empty') }}</td>
                </tr>
                <tr v-for="item in items" :key="item.id" class="border-t">
                  <td class="px-4 py-3 font-medium">{{ item.full_name }}</td>
                  <td class="px-4 py-3">{{ names(item.subjects) }}</td>
                  <td class="px-4 py-3">{{ names(item.levels) }}</td>
                  <td class="px-4 py-3">{{ t(`academicTeachers.statuses.${item.status}`) }}</td>
                  <td class="px-4 py-3 flex gap-2">
                    <button v-if="canUpdate" type="button" class="text-teal-800 hover:underline" @click="edit(item)">{{ t('forms.edit') }}</button>
                    <button v-if="canUpdate" type="button" class="text-rose-700 hover:underline" @click="remove(item)">{{ t('forms.delete') }}</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
          <div v-if="unassigned.length && !filters.subject_id && !filters.level_id" class="overflow-x-auto rounded-xl border bg-white">
            <div class="border-b px-4 py-3">
              <h3 class="font-semibold text-slate-700">{{ t('academicTeachers.unassigned') }}</h3>
            </div>
            <table class="min-w-full text-sm">
              <tbody>
                <tr v-for="item in unassigned" :key="item.id" class="border-t">
                  <td class="px-4 py-3 font-medium">{{ item.full_name }}</td>
                  <td class="px-4 py-3 flex gap-2">
                    <button v-if="canUpdate" type="button" class="text-teal-800 hover:underline" @click="edit(item)">{{ t('forms.edit') }}</button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
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
              <input type="checkbox" :checked="form.subject_ids.includes(subject.id)" @change="toggleId('subject_ids', subject.id)" />
              {{ label(subject) }}
            </label>
          </fieldset>
          <fieldset class="rounded-lg border p-3">
            <legend class="px-1 text-xs">{{ t('academicTeachers.levels') }}</legend>
            <label v-for="level in levels" :key="level.id" class="flex items-center gap-2 text-sm">
              <input type="checkbox" :checked="form.level_ids.includes(level.id)" @change="toggleId('level_ids', level.id)" />
              {{ label(level) }}
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
