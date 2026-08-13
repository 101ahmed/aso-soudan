<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  createStudent,
  deleteStudent,
  fetchStudentCatalog,
  fetchStudents,
  updateStudent,
} from '@/services/academic'
import { pickName } from '@/utils/localized'

const { t, locale } = useI18n()
const auth = useAuthStore()
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const items = ref([])
const meta = ref(null)
const catalog = ref({ academic_years: [], stages: [], subjects: [] })
const editingId = ref(null)
const filters = reactive({ search: '', status: '', page: 1 })
const form = reactive({
  first_name: '', last_name: '', birth_date: '', gender: '', academic_year_id: '',
  education_stage_id: '', level_id: '', status: 'active', notes: '', subject_ids: [],
})

const isTeacher = computed(() => auth.user?.roles?.some((r) => r.code === 'TEACHER'))
const canView = computed(() => auth.hasPermission('student.view'))
const canCreate = computed(() => auth.hasPermission('student.create') || isTeacher.value)
const canUpdate = computed(() => auth.hasPermission('student.update') || isTeacher.value)
const canDelete = computed(() => auth.hasPermission('student.delete') || auth.hasPermission('student.update') || isTeacher.value)
const canManage = computed(() => (editingId.value ? canUpdate.value : canCreate.value))
const levels = computed(() => {
  const stage = (catalog.value.stages || []).find((item) => String(item.id) === String(form.education_stage_id))
  return stage?.levels || []
})

function label(item) {
  if (!item) return ''
  return pickName(item, locale.value)
}

function resetForm() {
  editingId.value = null
  const currentYear = (catalog.value.academic_years || []).find((year) => year.is_current)
  Object.assign(form, {
    first_name: '', last_name: '', birth_date: '', gender: '',
    academic_year_id: currentYear?.id || catalog.value.academic_years?.[0]?.id || '',
    education_stage_id: '', level_id: '', status: 'active', notes: '', subject_ids: [],
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
    const response = await fetchStudents({ page: filters.page, search: filters.search || undefined, status: filters.status || undefined })
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
    first_name: item.first_name || '', last_name: item.last_name || '', birth_date: item.birth_date || '',
    gender: item.gender || '', academic_year_id: item.academic_year_id || '',
    education_stage_id: item.education_stage_id || '', level_id: item.level_id || '',
    status: item.status || 'active', notes: item.notes || '',
    subject_ids: (item.subjects || []).map((subject) => subject.id),
  })
}

function payload() {
  return {
    first_name: form.first_name, last_name: form.last_name, birth_date: form.birth_date || null,
    gender: form.gender || null, academic_year_id: form.academic_year_id || null,
    education_stage_id: form.education_stage_id || null, level_id: form.level_id || null,
    status: form.status, notes: form.notes || null, subject_ids: form.subject_ids,
  }
}

async function save() {
  if (!canManage.value) return
  saving.value = true
  error.value = ''
  try {
    if (editingId.value) await updateStudent(editingId.value, payload())
    else await createStudent(payload())
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
  if (!canDelete.value) return
  if (!confirm(t('academicStudents.confirmDelete', { name: item.full_name }))) return
  try {
    await deleteStudent(item.id)
    if (editingId.value === item.id) resetForm()
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

watch(() => form.education_stage_id, (next, prev) => {
  if (prev && next !== prev) {
    const stillValid = levels.value.some((level) => String(level.id) === String(form.level_id))
    if (!stillValid) form.level_id = ''
  }
})

onMounted(async () => {
  try { catalog.value = await fetchStudentCatalog() } catch { catalog.value = { academic_years: [], stages: [], subjects: [] } }
  resetForm()
  await load()
})
</script>

<template>
  <div class="space-y-5">
    <div>
      <h2 class="text-lg font-semibold text-[var(--rdp-forest)]">{{ t('academicStudents.title') }}</h2>
      <p class="mt-1 text-sm text-slate-600">{{ t('academicStudents.subtitle') }}</p>
    </div>
    <p v-if="!canView" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ t('academicStudents.forbidden') }}</p>
    <template v-else>
      <div class="grid gap-3 rounded-xl border bg-white p-4 md:grid-cols-3">
        <input v-model="filters.search" type="search" :placeholder="t('academicStudents.search')" class="rounded-md border px-3 py-2 text-sm" @keyup.enter="filters.page = 1; load()" />
        <select v-model="filters.status" class="rounded-md border px-3 py-2 text-sm">
          <option value="">{{ t('academicStudents.allStatuses') }}</option>
          <option value="active">{{ t('academicStudents.statuses.active') }}</option>
          <option value="pending">{{ t('academicStudents.statuses.pending') }}</option>
          <option value="inactive">{{ t('academicStudents.statuses.inactive') }}</option>
        </select>
        <button type="button" class="rounded-md border px-3 py-2 text-sm" @click="filters.page = 1; load()">{{ t('academicStudents.filter') }}</button>
      </div>
      <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
      <div class="grid gap-6 lg:grid-cols-[1.2fr_0.8fr]">
        <div class="overflow-x-auto rounded-xl border bg-white">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-start text-xs text-slate-500">
              <tr>
                <th class="px-4 py-3 font-medium">{{ t('academicStudents.name') }}</th>
                <th class="px-4 py-3 font-medium">{{ t('academicStudents.level') }}</th>
                <th class="px-4 py-3 font-medium">{{ t('academicStudents.subjects') }}</th>
                <th class="px-4 py-3 font-medium">{{ t('academicStudents.status') }}</th>
                <th class="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!loading && !items.length"><td colspan="5" class="px-4 py-6 text-center text-slate-500">{{ t('academicStudents.empty') }}</td></tr>
              <tr v-for="item in items" :key="item.id" class="border-t">
                <td class="px-4 py-3 font-medium">{{ item.full_name }}</td>
                <td class="px-4 py-3">{{ label(item.level) || '—' }}</td>
                <td class="px-4 py-3">{{ (item.subjects || []).map(label).join(' · ') || '—' }}</td>
                <td class="px-4 py-3">{{ t(`academicStudents.statuses.${item.status}`) }}</td>
                <td class="px-4 py-3 flex gap-2">
                  <button v-if="canUpdate" type="button" class="text-teal-800 hover:underline" @click="edit(item)">{{ t('forms.edit') }}</button>
                  <button v-if="canDelete" type="button" class="text-rose-700 hover:underline" @click="remove(item)">{{ t('forms.delete') }}</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <form v-if="canCreate || canUpdate" class="space-y-3 rounded-xl border bg-white p-5" @submit.prevent="save">
          <h3 class="font-semibold">{{ editingId ? t('academicStudents.editStudent') : t('academicStudents.newStudent') }}</h3>
          <div class="grid grid-cols-2 gap-2">
            <input v-model="form.first_name" required class="rounded border px-3 py-2 text-sm" :placeholder="t('forms.firstName')" />
            <input v-model="form.last_name" required class="rounded border px-3 py-2 text-sm" :placeholder="t('forms.lastName')" />
          </div>
          <input v-model="form.birth_date" type="date" class="w-full rounded border px-3 py-2 text-sm" />
          <select v-model="form.gender" class="w-full rounded border px-3 py-2 text-sm">
            <option value="">{{ t('academicStudents.gender') }}</option>
            <option value="male">{{ t('academicStudents.genders.male') }}</option>
            <option value="female">{{ t('academicStudents.genders.female') }}</option>
          </select>
          <select v-model="form.academic_year_id" class="w-full rounded border px-3 py-2 text-sm">
            <option value="">{{ t('academicStudents.year') }}</option>
            <option v-for="year in catalog.academic_years" :key="year.id" :value="year.id">{{ year.name }}</option>
          </select>
          <select v-model="form.education_stage_id" class="w-full rounded border px-3 py-2 text-sm">
            <option value="">{{ t('academicStudents.stage') }}</option>
            <option v-for="stage in catalog.stages" :key="stage.id" :value="stage.id">{{ label(stage) }}</option>
          </select>
          <select v-model="form.level_id" class="w-full rounded border px-3 py-2 text-sm">
            <option value="">{{ t('academicStudents.level') }}</option>
            <option v-for="level in levels" :key="level.id" :value="level.id">{{ label(level) }}</option>
          </select>
          <fieldset class="rounded-lg border p-3">
            <legend class="px-1 text-xs">{{ t('academicStudents.subjects') }}</legend>
            <label v-for="subject in catalog.subjects" :key="subject.id" class="flex items-center gap-2 text-sm">
              <input type="checkbox" :checked="form.subject_ids.includes(subject.id)" @change="toggleSubject(subject.id)" />
              {{ label(subject) }}
            </label>
          </fieldset>
          <div class="flex gap-2">
            <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white" :disabled="saving">{{ t('forms.save') }}</button>
            <button v-if="editingId" type="button" class="rounded border px-4 py-2 text-sm" @click="resetForm">{{ t('forms.cancel') }}</button>
          </div>
        </form>
      </div>
    </template>
  </div>
</template>
