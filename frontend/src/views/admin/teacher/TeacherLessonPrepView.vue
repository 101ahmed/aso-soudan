<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  createLessonPreparation,
  deleteLessonPreparation,
  downloadLessonPreparationPdf,
  fetchLessonPreparations,
  fetchLevels,
  fetchSubjects,
  updateLessonPreparation,
} from '@/services/academic'
import { pickName } from '@/utils/localized'

const { t, locale } = useI18n()
const auth = useAuthStore()

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const success = ref('')
const items = ref([])
const subjects = ref([])
const levels = ref([])
const editingId = ref(null)
const showForm = ref(false)
const downloadingId = ref(null)

const canWrite = computed(() =>
  auth.hasPermission('lesson_prep.create')
  || auth.hasPermission('lesson_prep.update')
  || auth.user?.roles?.some((r) => ['TEACHER', 'SUPER_ADMIN'].includes(r.code)),
)

const canDelete = computed(() =>
  auth.hasPermission('lesson_prep.delete')
  || auth.user?.roles?.some((r) => ['TEACHER', 'SUPER_ADMIN'].includes(r.code)),
)

const emptyForm = () => ({
  subject_id: '',
  level_id: '',
  lesson_date: '',
  title: '',
  unit: '',
  objectives: '',
  skills: '',
  concepts: '',
  intro: '',
  explanation: '',
  activities: '',
  group_work: '',
  assessment: '',
  conclusion: '',
})

const form = reactive(emptyForm())

function resetForm() {
  Object.assign(form, emptyForm())
  editingId.value = null
  error.value = ''
  success.value = ''
}

function startCreate() {
  resetForm()
  showForm.value = true
}

function startEdit(item) {
  editingId.value = item.id
  showForm.value = true
  error.value = ''
  success.value = ''
  Object.assign(form, {
    subject_id: item.subject_id || '',
    level_id: item.level_id || '',
    lesson_date: item.lesson_date || '',
    title: item.title || '',
    unit: item.unit || '',
    objectives: item.objectives || '',
    skills: item.skills || '',
    concepts: item.concepts || '',
    intro: item.intro || '',
    explanation: item.explanation || '',
    activities: item.activities || '',
    group_work: item.group_work || '',
    assessment: item.assessment || '',
    conclusion: item.conclusion || '',
  })
}

function cancelForm() {
  resetForm()
  showForm.value = false
}

function label(item) {
  return pickName(item, locale.value)
}

function formatDate(iso) {
  if (!iso) return '—'
  const date = new Date(`${iso}T00:00:00`)
  if (Number.isNaN(date.getTime())) return iso
  const locales = { ar: 'ar-SD', fr: 'fr-FR', en: 'en-GB' }
  return date.toLocaleDateString(locales[locale.value] || 'fr-FR', {
    weekday: 'long',
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const [prepResult, subjectResult, levelResult] = await Promise.allSettled([
      fetchLessonPreparations(),
      fetchSubjects(),
      fetchLevels(),
    ])
    if (prepResult.status === 'fulfilled') {
      items.value = prepResult.value || []
    } else {
      error.value = prepResult.reason?.response?.data?.message || prepResult.reason?.message || t('lessonPrep.loadError')
    }
    if (subjectResult.status === 'fulfilled') subjects.value = subjectResult.value || []
    if (levelResult.status === 'fulfilled') levels.value = levelResult.value || []
  } finally {
    loading.value = false
  }
}

async function save() {
  if (!canWrite.value) return
  saving.value = true
  error.value = ''
  success.value = ''
  const payload = {
    subject_id: Number(form.subject_id),
    level_id: Number(form.level_id),
    lesson_date: form.lesson_date,
    title: form.title,
    unit: form.unit || null,
    objectives: form.objectives || null,
    skills: form.skills || null,
    concepts: form.concepts || null,
    intro: form.intro || null,
    explanation: form.explanation || null,
    activities: form.activities || null,
    group_work: form.group_work || null,
    assessment: form.assessment || null,
    conclusion: form.conclusion || null,
  }
  try {
    if (editingId.value) {
      await updateLessonPreparation(editingId.value, payload)
      success.value = t('lessonPrep.updated')
    } else {
      await createLessonPreparation(payload)
      success.value = t('lessonPrep.created')
    }
    resetForm()
    showForm.value = false
    await load()
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors
      ? Object.values(errors).flat().join(' ')
      : (e.response?.data?.message || e.message)
  } finally {
    saving.value = false
  }
}

async function remove(item) {
  if (!canDelete.value) return
  if (!window.confirm(t('lessonPrep.confirmDelete', { title: item.title }))) return
  error.value = ''
  try {
    await deleteLessonPreparation(item.id)
    if (editingId.value === item.id) cancelForm()
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

function slugName(value) {
  return String(value || 'fiche')
    .replace(/[^\p{L}\p{N}]+/gu, '-')
    .replace(/^-|-$/g, '')
    .slice(0, 40) || 'fiche'
}

async function downloadPdf(item) {
  if (!item?.id) return
  downloadingId.value = item.id
  error.value = ''
  try {
    const { blob, contentType } = await downloadLessonPreparationPdf(item.id, { locale: locale.value })
    if (blob.type.includes('json') || String(contentType).includes('json')) {
      const payload = JSON.parse(await blob.text())
      throw new Error(payload.message || t('lessonPrep.downloadFailed'))
    }
    const url = URL.createObjectURL(blob)
    const isPdf = String(contentType).includes('pdf') || blob.type.includes('pdf')
    const a = document.createElement('a')
    a.href = url
    a.download = `lesson-prep-${item.id}-${item.lesson_date || slugName(item.title)}.${isPdf ? 'pdf' : 'html'}`
    document.body.appendChild(a)
    a.click()
    a.remove()
    setTimeout(() => URL.revokeObjectURL(url), 1500)
  } catch (e) {
    const data = e.response?.data
    if (data instanceof Blob) {
      try {
        const payload = JSON.parse(await data.text())
        error.value = payload.message || t('lessonPrep.downloadFailed')
      } catch {
        error.value = t('lessonPrep.downloadFailed')
      }
    } else {
      error.value = e.message || e.response?.data?.message || t('lessonPrep.downloadFailed')
    }
  } finally {
    downloadingId.value = null
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold text-[var(--rdp-forest)]">{{ t('lessonPrep.title') }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ t('lessonPrep.hint') }}</p>
      </div>
      <button
        v-if="canWrite && !showForm"
        type="button"
        class="rounded bg-teal-800 px-4 py-2 text-sm text-white"
        @click="startCreate"
      >
        {{ t('lessonPrep.create') }}
      </button>
    </div>

    <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
    <p v-if="success" class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ success }}</p>
    <p v-else-if="loading" class="text-sm text-slate-500">{{ t('academicAttendance.loading') }}</p>

    <form
      v-if="showForm"
      class="space-y-5 rounded-xl border bg-white p-5"
      @submit.prevent="save"
    >
      <div class="flex items-center justify-between gap-3">
        <h3 class="font-semibold text-[var(--rdp-forest)]">
          {{ editingId ? t('lessonPrep.edit') : t('lessonPrep.create') }}
        </h3>
        <button type="button" class="text-sm text-slate-600 hover:underline" @click="cancelForm">
          {{ t('forms.cancel') }}
        </button>
      </div>

      <section class="space-y-3">
        <h4 class="text-sm font-semibold tracking-wide text-slate-500 uppercase">{{ t('lessonPrep.sectionBasics') }}</h4>
        <div class="grid gap-3 md:grid-cols-2">
          <label class="block text-sm">
            <span class="mb-1 block text-slate-600">{{ t('lessonPrep.subject') }}</span>
            <select v-model="form.subject_id" required class="w-full rounded border px-3 py-2">
              <option value="">{{ t('lessonPrep.select') }}</option>
              <option v-for="subject in subjects" :key="subject.id" :value="subject.id">{{ label(subject) }}</option>
            </select>
          </label>
          <label class="block text-sm">
            <span class="mb-1 block text-slate-600">{{ t('lessonPrep.level') }}</span>
            <select v-model="form.level_id" required class="w-full rounded border px-3 py-2">
              <option value="">{{ t('lessonPrep.select') }}</option>
              <option v-for="level in levels" :key="level.id" :value="level.id">{{ label(level) }}</option>
            </select>
          </label>
          <label class="block text-sm">
            <span class="mb-1 block text-slate-600">{{ t('lessonPrep.date') }}</span>
            <input v-model="form.lesson_date" type="date" required class="w-full rounded border px-3 py-2" />
          </label>
          <label class="block text-sm">
            <span class="mb-1 block text-slate-600">{{ t('lessonPrep.lessonTitle') }}</span>
            <input v-model="form.title" required maxlength="190" class="w-full rounded border px-3 py-2" />
          </label>
          <label class="block text-sm md:col-span-2">
            <span class="mb-1 block text-slate-600">{{ t('lessonPrep.unit') }}</span>
            <input v-model="form.unit" maxlength="190" class="w-full rounded border px-3 py-2" />
          </label>
          <label class="block text-sm md:col-span-2">
            <span class="mb-1 block text-slate-600">{{ t('lessonPrep.objectives') }}</span>
            <textarea v-model="form.objectives" rows="3" class="w-full rounded border px-3 py-2" />
          </label>
          <label class="block text-sm md:col-span-2">
            <span class="mb-1 block text-slate-600">{{ t('lessonPrep.skills') }}</span>
            <textarea v-model="form.skills" rows="3" class="w-full rounded border px-3 py-2" />
          </label>
          <label class="block text-sm md:col-span-2">
            <span class="mb-1 block text-slate-600">{{ t('lessonPrep.concepts') }}</span>
            <textarea v-model="form.concepts" rows="3" class="w-full rounded border px-3 py-2" />
          </label>
        </div>
      </section>

      <section class="space-y-3 border-t pt-4">
        <h4 class="text-sm font-semibold tracking-wide text-slate-500 uppercase">{{ t('lessonPrep.sectionPlan') }}</h4>
        <label class="block text-sm">
          <span class="mb-1 block text-slate-600">{{ t('lessonPrep.intro') }}</span>
          <textarea v-model="form.intro" rows="3" class="w-full rounded border px-3 py-2" />
        </label>
        <label class="block text-sm">
          <span class="mb-1 block text-slate-600">{{ t('lessonPrep.explanation') }}</span>
          <textarea v-model="form.explanation" rows="4" class="w-full rounded border px-3 py-2" />
        </label>
        <label class="block text-sm">
          <span class="mb-1 block text-slate-600">{{ t('lessonPrep.activities') }}</span>
          <textarea v-model="form.activities" rows="3" class="w-full rounded border px-3 py-2" />
        </label>
        <label class="block text-sm">
          <span class="mb-1 block text-slate-600">{{ t('lessonPrep.groupWork') }}</span>
          <textarea v-model="form.group_work" rows="3" class="w-full rounded border px-3 py-2" />
        </label>
        <label class="block text-sm">
          <span class="mb-1 block text-slate-600">{{ t('lessonPrep.assessment') }}</span>
          <textarea v-model="form.assessment" rows="3" class="w-full rounded border px-3 py-2" />
        </label>
        <label class="block text-sm">
          <span class="mb-1 block text-slate-600">{{ t('lessonPrep.conclusion') }}</span>
          <textarea v-model="form.conclusion" rows="3" class="w-full rounded border px-3 py-2" />
        </label>
      </section>

      <div class="flex flex-wrap items-center gap-3">
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white disabled:opacity-50" :disabled="saving">
          {{ saving ? t('academicAttendance.saving') : t('forms.save') }}
        </button>
        <button
          v-if="editingId"
          type="button"
          class="rounded border border-teal-800 px-4 py-2 text-sm text-teal-800 disabled:opacity-50"
          :disabled="downloadingId === editingId"
          @click="downloadPdf(items.find((item) => item.id === editingId) || { id: editingId, title: form.title, lesson_date: form.lesson_date })"
        >
          {{ downloadingId === editingId ? t('lessonPrep.downloading') : t('lessonPrep.downloadPdf') }}
        </button>
      </div>
    </form>

    <section class="overflow-hidden rounded-xl border bg-white">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-start text-xs text-slate-500">
          <tr>
            <th class="px-4 py-3 font-medium">{{ t('lessonPrep.date') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('lessonPrep.subject') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('lessonPrep.level') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('lessonPrep.lessonTitle') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('lessonPrep.unit') }}</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in items" :key="item.id" class="border-t">
            <td class="px-4 py-3">
              <p class="font-medium">{{ formatDate(item.lesson_date) }}</p>
              <p class="text-xs text-slate-500">{{ item.lesson_date }}</p>
            </td>
            <td class="px-4 py-3">{{ label(item.subject) || '—' }}</td>
            <td class="px-4 py-3">{{ label(item.level) || '—' }}</td>
            <td class="px-4 py-3 font-medium">{{ item.title }}</td>
            <td class="px-4 py-3 text-slate-600">{{ item.unit || '—' }}</td>
            <td class="px-4 py-3 text-end">
              <button type="button" class="text-xs font-semibold text-[var(--rdp-forest)] hover:underline disabled:opacity-50" :disabled="downloadingId === item.id" @click="downloadPdf(item)">
                {{ downloadingId === item.id ? t('lessonPrep.downloading') : t('lessonPrep.downloadPdf') }}
              </button>
              <button v-if="canWrite" type="button" class="ms-3 text-xs font-semibold text-[var(--rdp-forest)] hover:underline" @click="startEdit(item)">
                {{ t('forms.edit') }}
              </button>
              <button v-if="canDelete" type="button" class="ms-3 text-xs text-rose-700 hover:underline" @click="remove(item)">
                {{ t('forms.delete') }}
              </button>
            </td>
          </tr>
          <tr v-if="!loading && !items.length">
            <td colspan="6" class="px-4 py-8 text-center text-slate-500">
              {{ t('lessonPrep.empty') }}
            </td>
          </tr>
        </tbody>
      </table>
    </section>
  </div>
</template>
