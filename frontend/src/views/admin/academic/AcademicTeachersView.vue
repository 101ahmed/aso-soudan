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

function hasId(list, id) {
  return (list || []).some((item) => String(item.id) === String(id))
}

function resetForm() {
  editingId.value = null
  Object.assign(form, {
    first_name: '', last_name: '', email: '', phone: '', locale: 'ar', status: 'active',
    hired_on: '', notes: '', password: '', password_confirmation: '',
    subject_ids: [],
    level_ids: [],
  })
}

function toggleId(listName, id) {
  const value = Number(id)
  if (form[listName].map(Number).includes(value)) {
    form[listName] = form[listName].filter((item) => Number(item) !== value)
  } else {
    form[listName].push(value)
  }
}

function isChecked(listName, id) {
  return form[listName].map(Number).includes(Number(id))
}

function names(list) {
  return (list || []).map(label).join(' · ') || '—'
}

function teachersForSubject(subjectId) {
  return items.value.filter((item) => {
    if (!hasId(item.subjects, subjectId)) return false
    if (filters.level_id && !hasId(item.levels, filters.level_id)) return false
    return true
  })
}

function teachersForLevel(levelId) {
  return items.value.filter((item) => {
    if (!hasId(item.levels, levelId)) return false
    if (filters.subject_id && !hasId(item.subjects, filters.subject_id)) return false
    return true
  })
}

function teachersForSubjectAndLevel(subjectId, levelId) {
  return items.value.filter((item) => hasId(item.subjects, subjectId) && hasId(item.levels, levelId))
}

const visibleSubjects = computed(() => {
  if (!filters.subject_id) return subjects.value
  return subjects.value.filter((subject) => String(subject.id) === String(filters.subject_id))
})

const visibleLevels = computed(() => {
  if (!filters.level_id) return levels.value
  return levels.value.filter((level) => String(level.id) === String(filters.level_id))
})

const unassigned = computed(() => items.value.filter((item) => {
  const noSubject = !(item.subjects || []).length
  const noLevel = !(item.levels || []).length
  return noSubject || noLevel
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
    subject_ids: (item.subjects || []).map((subject) => Number(subject.id)),
    level_ids: (item.levels || []).map((level) => Number(level.id)),
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
          :class="!filters.subject_id ? 'bg-teal-800 text-white' : 'border bg-white text-slate-700'"
          @click="selectSubject('')"
        >
          {{ t('academicTeachers.allSubjects') }}
        </button>
        <button
          v-for="subject in subjects"
          :key="`subject-${subject.id}`"
          type="button"
          class="rounded-full px-3 py-1.5 text-sm"
          :class="String(filters.subject_id) === String(subject.id) ? 'bg-teal-800 text-white' : 'border bg-white text-slate-700'"
          @click="selectSubject(String(subject.id))"
        >
          {{ label(subject) }}
          <span class="opacity-80">({{ teachersForSubject(subject.id).length }})</span>
        </button>
      </div>
      <div class="flex flex-wrap gap-2">
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
          :key="`level-${level.id}`"
          type="button"
          class="rounded-full px-3 py-1.5 text-sm"
          :class="String(filters.level_id) === String(level.id) ? 'bg-teal-800 text-white' : 'border bg-white text-slate-700'"
          @click="selectLevel(String(level.id))"
        >
          {{ label(level) }}
          <span class="opacity-80">({{ teachersForLevel(level.id).length }})</span>
        </button>
      </div>

      <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
      <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <div class="space-y-6">
          <section class="space-y-3">
            <h3 class="text-base font-semibold text-[var(--rdp-forest)]">{{ t('academicTeachers.bySubject') }}</h3>
            <div v-for="subject in visibleSubjects" :key="`group-subject-${subject.id}`" class="overflow-hidden rounded-xl border bg-white">
              <div class="flex items-center justify-between border-b px-4 py-3">
                <h4 class="font-semibold">{{ label(subject) }}</h4>
                <p class="text-xs text-slate-500">{{ teachersForSubject(subject.id).length }} {{ t('academicTeachers.teachersCount') }}</p>
              </div>
              <p v-if="!teachersForSubject(subject.id).length" class="px-4 py-6 text-center text-sm text-slate-500">{{ t('academicTeachers.emptyGroup') }}</p>
              <div
                v-for="level in visibleLevels"
                v-show="teachersForSubjectAndLevel(subject.id, level.id).length"
                :key="`subject-${subject.id}-level-${level.id}`"
                class="border-b last:border-b-0"
              >
                <p class="bg-slate-50 px-4 py-2 text-xs font-medium text-slate-600">{{ label(level) }}</p>
                <table class="min-w-full text-sm">
                  <tbody>
                    <tr v-if="!teachersForSubjectAndLevel(subject.id, level.id).length">
                      <td class="px-4 py-3 text-slate-400">{{ t('academicTeachers.emptyGroup') }}</td>
                    </tr>
                    <tr v-for="item in teachersForSubjectAndLevel(subject.id, level.id)" :key="item.id" class="border-t">
                      <td class="px-4 py-3 font-medium">{{ item.full_name }}</td>
                      <td class="px-4 py-3 flex gap-2">
                        <button v-if="canUpdate" type="button" class="text-teal-800 hover:underline" @click="edit(item)">{{ t('forms.edit') }}</button>
                        <button v-if="canUpdate" type="button" class="text-rose-700 hover:underline" @click="remove(item)">{{ t('forms.delete') }}</button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </section>

          <section class="space-y-3">
            <h3 class="text-base font-semibold text-[var(--rdp-forest)]">{{ t('academicTeachers.byLevel') }}</h3>
            <div v-for="level in visibleLevels" :key="`group-level-${level.id}`" class="overflow-hidden rounded-xl border bg-white">
              <div class="flex items-center justify-between border-b px-4 py-3">
                <h4 class="font-semibold">{{ label(level) }}</h4>
                <p class="text-xs text-slate-500">{{ teachersForLevel(level.id).length }} {{ t('academicTeachers.teachersCount') }}</p>
              </div>
              <p v-if="!teachersForLevel(level.id).length" class="px-4 py-6 text-center text-sm text-slate-500">{{ t('academicTeachers.emptyGroup') }}</p>
              <div
                v-for="subject in visibleSubjects"
                v-show="teachersForSubjectAndLevel(subject.id, level.id).length"
                :key="`level-${level.id}-subject-${subject.id}`"
                class="border-b last:border-b-0"
              >
                <p class="bg-slate-50 px-4 py-2 text-xs font-medium text-slate-600">{{ label(subject) }}</p>
                <table class="min-w-full text-sm">
                  <tbody>
                    <tr v-if="!teachersForSubjectAndLevel(subject.id, level.id).length">
                      <td class="px-4 py-3 text-slate-400">{{ t('academicTeachers.emptyGroup') }}</td>
                    </tr>
                    <tr v-for="item in teachersForSubjectAndLevel(subject.id, level.id)" :key="item.id" class="border-t">
                      <td class="px-4 py-3 font-medium">{{ item.full_name }}</td>
                      <td class="px-4 py-3 flex gap-2">
                        <button v-if="canUpdate" type="button" class="text-teal-800 hover:underline" @click="edit(item)">{{ t('forms.edit') }}</button>
                        <button v-if="canUpdate" type="button" class="text-rose-700 hover:underline" @click="remove(item)">{{ t('forms.delete') }}</button>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </section>

          <div v-if="unassigned.length && !filters.subject_id && !filters.level_id" class="overflow-x-auto rounded-xl border bg-white">
            <div class="border-b px-4 py-3">
              <h3 class="font-semibold text-slate-700">{{ t('academicTeachers.unassigned') }}</h3>
            </div>
            <table class="min-w-full text-sm">
              <tbody>
                <tr v-for="item in unassigned" :key="item.id" class="border-t">
                  <td class="px-4 py-3 font-medium">{{ item.full_name }}</td>
                  <td class="px-4 py-3 text-slate-500">{{ names(item.subjects) }} · {{ names(item.levels) }}</td>
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
              <input type="checkbox" :checked="isChecked('subject_ids', subject.id)" @change="toggleId('subject_ids', subject.id)" />
              {{ label(subject) }}
            </label>
          </fieldset>
          <fieldset class="rounded-lg border p-3">
            <legend class="px-1 text-xs">{{ t('academicTeachers.levels') }}</legend>
            <label v-for="level in levels" :key="level.id" class="flex items-center gap-2 text-sm">
              <input type="checkbox" :checked="isChecked('level_ids', level.id)" @change="toggleId('level_ids', level.id)" />
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
