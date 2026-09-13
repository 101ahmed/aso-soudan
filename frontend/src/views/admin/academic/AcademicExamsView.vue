<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  createExam,
  deleteExam,
  fetchExamCatalog,
  fetchExams,
  updateExam,
} from '@/services/academic'
import { academicBaseFromPath } from '@/utils/academicPaths'
import { pickName } from '@/utils/localized'

const { t, locale } = useI18n()
const auth = useAuthStore()
const route = useRoute()
const base = computed(() => academicBaseFromPath(route.path))

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const catalog = ref({ academic_years: [], levels: [], subjects: [], periods: [], current_year: null, can_create: false })
const items = ref([])
const editingId = ref(null)

const filters = reactive({
  academic_year_id: '',
  level_id: '',
  subject_id: '',
  period: '',
  search: '',
})

const form = reactive(emptyForm())
const canView = computed(() => auth.hasPermission('exam.view'))
const canCreate = computed(() => auth.hasPermission('exam.create'))
const canUpdate = computed(() => auth.hasPermission('exam.update'))
const canDelete = computed(() => auth.hasPermission('exam.delete'))

function emptyForm() {
  return {
    title: '',
    academic_year_id: '',
    level_id: '',
    subject_id: '',
    period: 'term1',
    exam_date: '',
    max_score: 20,
    pass_score: 10,
    notes: '',
  }
}

function label(item) {
  return pickName(item, locale.value)
}

function resetForm() {
  editingId.value = null
  Object.assign(form, emptyForm())
  form.academic_year_id = catalog.value.current_year?.id || catalog.value.academic_years?.[0]?.id || ''
}

function edit(item) {
  editingId.value = item.id
  form.title = item.title
  form.academic_year_id = item.academic_year_id
  form.level_id = item.level_id
  form.subject_id = item.subject_id
  form.period = item.period
  form.exam_date = item.exam_date
  form.max_score = item.max_score
  form.pass_score = item.pass_score
  form.notes = item.notes || ''
}

async function loadCatalog() {
  catalog.value = await fetchExamCatalog()
  if (!filters.academic_year_id) {
    filters.academic_year_id = catalog.value.current_year?.id || catalog.value.academic_years?.[0]?.id || ''
  }
  if (!form.academic_year_id) {
    form.academic_year_id = filters.academic_year_id
  }
}

async function load() {
  if (!canView.value) return
  loading.value = true
  error.value = ''
  try {
    const data = await fetchExams({
      academic_year_id: filters.academic_year_id || undefined,
      level_id: filters.level_id || undefined,
      subject_id: filters.subject_id || undefined,
      period: filters.period || undefined,
      search: filters.search || undefined,
    })
    items.value = data.data || []
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  error.value = ''
  try {
    const payload = {
      ...form,
      max_score: Number(form.max_score),
      pass_score: Number(form.pass_score),
    }
    if (editingId.value) await updateExam(editingId.value, payload)
    else await createExam(payload)
    resetForm()
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || Object.values(e.response?.data?.errors || {})[0]?.[0] || e.message
  } finally {
    saving.value = false
  }
}

async function remove(item) {
  if (!confirm(t('academicExams.confirmDelete'))) return
  try {
    await deleteExam(item.id)
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

onMounted(async () => {
  if (!canView.value) return
  try {
    await loadCatalog()
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
})
</script>

<template>
  <div class="space-y-4">
    <div>
      <h2 class="text-lg font-semibold text-[var(--rdp-forest)]">{{ t('academicExams.title') }}</h2>
      <p class="mt-1 text-sm text-slate-600">{{ t('academicExams.hint') }}</p>
    </div>
    <p v-if="!canView" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ t('academicExams.forbidden') }}</p>
    <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>

    <template v-if="canView">
      <div class="flex flex-wrap gap-2">
        <select v-model="filters.academic_year_id" class="rounded-md border px-3 py-2 text-sm" @change="load">
          <option v-for="year in catalog.academic_years" :key="year.id" :value="year.id">{{ year.name }}</option>
        </select>
        <select v-model="filters.level_id" class="rounded-md border px-3 py-2 text-sm" @change="load">
          <option value="">{{ t('academicExams.allLevels') }}</option>
          <option v-for="level in catalog.levels" :key="level.id" :value="level.id">{{ label(level) }}</option>
        </select>
        <select v-model="filters.subject_id" class="rounded-md border px-3 py-2 text-sm" @change="load">
          <option value="">{{ t('academicExams.allSubjects') }}</option>
          <option v-for="subject in catalog.subjects" :key="subject.id" :value="subject.id">{{ label(subject) }}</option>
        </select>
        <select v-model="filters.period" class="rounded-md border px-3 py-2 text-sm" @change="load">
          <option value="">{{ t('academicExams.allPeriods') }}</option>
          <option v-for="period in catalog.periods" :key="period" :value="period">{{ t(`academicExams.periods.${period}`) }}</option>
        </select>
        <input v-model="filters.search" type="search" class="rounded-md border px-3 py-2 text-sm" :placeholder="t('academicExams.search')" @keyup.enter="load" />
        <button type="button" class="rounded-md border px-3 py-2 text-sm" @click="load">{{ t('academicExams.filter') }}</button>
      </div>

      <form v-if="canCreate || (editingId && canUpdate)" class="grid gap-3 rounded-xl border bg-white p-4 md:grid-cols-3" @submit.prevent="save">
        <h3 class="md:col-span-3 font-semibold">{{ editingId ? t('academicExams.edit') : t('academicExams.new') }}</h3>
        <input v-model="form.title" required class="rounded border px-3 py-2 text-sm md:col-span-2" :placeholder="t('academicExams.examTitle')" />
        <input v-model="form.exam_date" type="date" required class="rounded border px-3 py-2 text-sm" />
        <select v-model="form.academic_year_id" class="rounded border px-3 py-2 text-sm">
          <option v-for="year in catalog.academic_years" :key="year.id" :value="year.id">{{ year.name }}</option>
        </select>
        <select v-model="form.level_id" required class="rounded border px-3 py-2 text-sm">
          <option value="" disabled>{{ t('academicExams.level') }}</option>
          <option v-for="level in catalog.levels" :key="level.id" :value="level.id">{{ label(level) }}</option>
        </select>
        <select v-model="form.subject_id" required class="rounded border px-3 py-2 text-sm">
          <option value="" disabled>{{ t('academicExams.subject') }}</option>
          <option v-for="subject in catalog.subjects" :key="subject.id" :value="subject.id">{{ label(subject) }}</option>
        </select>
        <select v-model="form.period" class="rounded border px-3 py-2 text-sm">
          <option v-for="period in catalog.periods" :key="period" :value="period">{{ t(`academicExams.periods.${period}`) }}</option>
        </select>
        <input v-model="form.max_score" type="number" min="0.01" step="0.01" class="rounded border px-3 py-2 text-sm" :placeholder="t('academicExams.maxScore')" />
        <input v-model="form.pass_score" type="number" min="0" step="0.01" class="rounded border px-3 py-2 text-sm" :placeholder="t('academicExams.passScore')" />
        <textarea v-model="form.notes" rows="2" class="rounded border px-3 py-2 text-sm md:col-span-3" :placeholder="t('academicExams.notes')" />
        <div class="md:col-span-3 flex gap-2">
          <button type="submit" class="rounded-md bg-teal-800 px-4 py-2 text-sm text-white" :disabled="saving">{{ t('academicExams.save') }}</button>
          <button v-if="editingId" type="button" class="rounded-md border px-4 py-2 text-sm" @click="resetForm">{{ t('academicExams.cancel') }}</button>
        </div>
      </form>

      <div class="overflow-hidden rounded-xl border bg-white">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-start text-xs text-slate-500">
            <tr>
              <th class="px-4 py-3 font-medium">{{ t('academicExams.examTitle') }}</th>
              <th class="px-4 py-3 font-medium">{{ t('academicExams.level') }}</th>
              <th class="px-4 py-3 font-medium">{{ t('academicExams.subject') }}</th>
              <th class="px-4 py-3 font-medium">{{ t('academicExams.date') }}</th>
              <th class="px-4 py-3 font-medium">{{ t('academicExams.maxScore') }}</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in items" :key="item.id" class="border-t">
              <td class="px-4 py-3">
                <p class="font-medium">{{ item.title }}</p>
                <p class="text-xs text-slate-500">{{ t(`academicExams.periods.${item.period}`) }}</p>
              </td>
              <td class="px-4 py-3">{{ label(item.level) }}</td>
              <td class="px-4 py-3">{{ label(item.subject) }}</td>
              <td class="px-4 py-3">{{ item.exam_date }}</td>
              <td class="px-4 py-3">{{ item.max_score }} / {{ t('academicExams.passShort') }} {{ item.pass_score }}</td>
              <td class="px-4 py-3 text-end space-x-2 space-x-reverse">
                <RouterLink :to="`${base}/exams/${item.id}`" class="text-xs font-semibold text-[var(--rdp-forest)] hover:underline">{{ t('academicExams.openSheet') }}</RouterLink>
                <button v-if="item.can_manage && canUpdate" type="button" class="text-xs text-slate-600 hover:underline" @click="edit(item)">{{ t('academicExams.edit') }}</button>
                <button v-if="item.can_manage && canDelete" type="button" class="text-xs text-rose-700 hover:underline" @click="remove(item)">{{ t('academicExams.delete') }}</button>
              </td>
            </tr>
            <tr v-if="!loading && !items.length">
              <td colspan="6" class="px-4 py-8 text-center text-slate-500">{{ t('academicExams.empty') }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </div>
</template>
