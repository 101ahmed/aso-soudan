<script setup>
import { computed, onMounted, reactive, ref, shallowRef, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  downloadStudentAcademicReportPdf,
  downloadStudentDossierPdf,
  fetchStudent,
  fetchStudentCatalog,
  fetchStudents,
  updateStudent,
} from '@/services/academic'
import { academicBaseFromPath } from '@/utils/academicPaths'
import { pickName } from '@/utils/localized'
import { prepareUploadImage } from '@/utils/prepareUploadImage'

const { t, locale } = useI18n()
const auth = useAuthStore()
const route = useRoute()
const base = computed(() => academicBaseFromPath(route.path))
const loading = ref(false)
const saving = ref(false)
const downloading = ref(false)
const downloadingResults = ref(false)
const error = ref('')
const success = ref('')
const search = ref('')
const results = ref([])
const selected = ref(null)
const catalog = ref({ academic_years: [], stages: [], subjects: [] })
const photoFile = shallowRef(null)
const photoPreview = ref(null)
const photoInput = ref(null)
const form = reactive({
  birth_date: '',
  education_stage_id: '',
  level_id: '',
  notes: '',
  remove_photo: false,
})

const isTeacher = computed(() => auth.user?.roles?.some((r) => r.code === 'TEACHER'))
const canView = computed(() => auth.hasPermission('student.view') || isTeacher.value)
const canUpdate = computed(() => auth.hasPermission('student.update') || isTeacher.value)
const canViewExams = computed(() => auth.hasPermission('exam.view'))
const levels = computed(() => {
  const stage = (catalog.value.stages || []).find((item) => String(item.id) === String(form.education_stage_id))
  return stage?.levels || []
})
const formAge = computed(() => yearsFromBirthDate(form.birth_date))
const selectedAge = computed(() => selected.value?.age ?? yearsFromBirthDate(selected.value?.birth_date))

function label(item) {
  if (!item) return ''
  return pickName(item, locale.value)
}

function yearsFromBirthDate(value) {
  if (!value) return null
  const birth = new Date(`${value}T00:00:00`)
  if (Number.isNaN(birth.getTime())) return null
  const now = new Date()
  let years = now.getFullYear() - birth.getFullYear()
  const monthDiff = now.getMonth() - birth.getMonth()
  if (monthDiff < 0 || (monthDiff === 0 && now.getDate() < birth.getDate())) years -= 1
  return years >= 0 ? years : null
}

function ageText(years) {
  if (years == null) return '—'
  return `${years} ${t('academicStudents.years')}`
}

function resetPhotoInput() {
  photoFile.value = null
  if (photoInput.value) photoInput.value.value = ''
}

function applyStudent(item) {
  selected.value = item
  Object.assign(form, {
    birth_date: item.birth_date || '',
    education_stage_id: item.education_stage_id || item.level?.education_stage_id || '',
    level_id: item.level_id || '',
    notes: item.notes || '',
    remove_photo: false,
  })
  photoPreview.value = item.photo_url || null
  resetPhotoInput()
  error.value = ''
  success.value = ''
}

async function loadResults() {
  if (!canView.value) return
  loading.value = true
  error.value = ''
  success.value = ''
  try {
    const response = await fetchStudents({
      search: search.value || undefined,
      per_page: 50,
    })
    results.value = response.data || []
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

async function pick(item) {
  applyStudent(item)
  try {
    const fresh = await fetchStudent(item.id)
    applyStudent(fresh)
  } catch {
    /* keep list item */
  }
}

async function onPhotoPick(event) {
  const file = event.target.files?.[0] || null
  form.remove_photo = false
  if (!file) {
    photoFile.value = null
    return
  }
  try {
    photoFile.value = await prepareUploadImage(file)
    photoPreview.value = URL.createObjectURL(photoFile.value)
  } catch {
    photoFile.value = file
    photoPreview.value = URL.createObjectURL(file)
  }
}

function clearPhoto() {
  photoFile.value = null
  form.remove_photo = true
  photoPreview.value = null
  if (photoInput.value) photoInput.value.value = ''
}

function payload() {
  const student = selected.value || {}
  return {
    first_name: student.first_name,
    last_name: student.last_name,
    birth_date: form.birth_date || null,
    gender: student.gender || null,
    academic_year_id: student.academic_year_id || null,
    education_stage_id: form.education_stage_id || null,
    level_id: form.level_id || null,
    status: student.status || 'active',
    notes: form.notes || '',
    subject_ids: (student.subjects || []).map((subject) => subject.id),
    photo: photoFile.value || undefined,
    remove_photo: form.remove_photo || undefined,
  }
}

async function save() {
  if (!canUpdate.value || !selected.value) return
  saving.value = true
  error.value = ''
  success.value = ''
  try {
    const updated = await updateStudent(selected.value.id, payload())
    applyStudent(updated)
    results.value = results.value.map((item) => (item.id === updated.id ? { ...item, ...updated } : item))
    success.value = t('academicStudentFile.saved')
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || e.message
  } finally {
    saving.value = false
  }
}

async function downloadPdf() {
  if (!selected.value?.id) return
  downloading.value = true
  error.value = ''
  try {
    const { blob, contentType } = await downloadStudentDossierPdf(selected.value.id, { locale: locale.value })
    if (blob.type.includes('json') || String(contentType).includes('json')) {
      const payloadJson = JSON.parse(await blob.text())
      throw new Error(payloadJson.message || t('academicStudents.downloadFailed'))
    }
    const url = URL.createObjectURL(blob)
    const isPdf = String(contentType).includes('pdf') || blob.type.includes('pdf')
    const a = document.createElement('a')
    a.href = url
    a.download = `dossier-${selected.value.id}.${isPdf ? 'pdf' : 'html'}`
    document.body.appendChild(a)
    a.click()
    a.remove()
    setTimeout(() => URL.revokeObjectURL(url), 1500)
  } catch (e) {
    const data = e.response?.data
    if (data instanceof Blob) {
      try {
        const payloadJson = JSON.parse(await data.text())
        error.value = payloadJson.message || t('academicStudents.downloadFailed')
      } catch {
        error.value = t('academicStudents.downloadFailed')
      }
    } else {
      error.value = e.message || e.response?.data?.message || t('academicStudents.downloadFailed')
    }
  } finally {
    downloading.value = false
  }
}

async function downloadExamResultsPdf() {
  if (!selected.value?.id) return
  downloadingResults.value = true
  error.value = ''
  try {
    await downloadStudentAcademicReportPdf(selected.value.id, { locale: locale.value, period: 'term1' })
  } catch (e) {
    error.value = e.response?.data?.message || t('academicAchievement.downloadFailed')
  } finally {
    downloadingResults.value = false
  }
}

watch(() => form.education_stage_id, (next, prev) => {
  if (prev && next !== prev) {
    const stillValid = levels.value.some((level) => String(level.id) === String(form.level_id))
    if (!stillValid) form.level_id = ''
  }
})

onMounted(async () => {
  try {
    catalog.value = await fetchStudentCatalog()
  } catch {
    catalog.value = { academic_years: [], stages: [], subjects: [] }
  }
  await loadResults()
})
</script>

<template>
  <div class="space-y-5">
    <div>
      <h2 class="text-lg font-semibold text-[var(--rdp-forest)]">{{ t('academicStudentFile.title') }}</h2>
      <p class="mt-1 text-sm text-slate-600">{{ t('academicStudentFile.subtitle') }}</p>
    </div>
    <p v-if="!canView" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ t('academicStudents.forbidden') }}</p>
    <template v-else>
      <div class="grid gap-3 rounded-xl border bg-white p-4 md:grid-cols-[1fr_auto]">
        <input
          v-model="search"
          type="search"
          :placeholder="t('academicStudentFile.search')"
          class="rounded-md border px-3 py-2 text-sm"
          @keyup.enter="loadResults"
        />
        <button type="button" class="rounded-md border px-3 py-2 text-sm" @click="loadResults">
          {{ t('academicStudents.filter') }}
        </button>
      </div>
      <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
      <p v-if="success" class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ success }}</p>
      <div class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
        <div class="overflow-hidden rounded-xl border bg-white">
          <div class="border-b px-4 py-3">
            <h3 class="font-semibold text-[var(--rdp-forest)]">{{ t('academicStudentFile.pickStudent') }}</h3>
          </div>
          <p v-if="loading" class="px-4 py-6 text-sm text-slate-500">{{ t('academicStudentFile.loading') }}</p>
          <p v-else-if="!results.length" class="px-4 py-6 text-sm text-slate-500">{{ t('academicStudents.empty') }}</p>
          <ul v-else class="max-h-[32rem] divide-y overflow-y-auto">
            <li v-for="item in results" :key="item.id">
              <button
                type="button"
                class="flex w-full items-center justify-between gap-3 px-4 py-3 text-start text-sm hover:bg-slate-50"
                :class="selected?.id === item.id ? 'bg-teal-50' : ''"
                @click="pick(item)"
              >
                <span>
                  <span class="block font-medium">{{ item.full_name }}</span>
                  <span class="text-xs text-slate-500">{{ label(item.level) || t('academicStudents.unassigned') }}</span>
                </span>
                <span class="text-xs text-slate-500">{{ ageText(item.age ?? yearsFromBirthDate(item.birth_date)) }}</span>
              </button>
            </li>
          </ul>
        </div>
        <div v-if="!selected" class="rounded-xl border bg-white p-8 text-center text-sm text-slate-500">
          {{ t('academicStudentFile.chooseHint') }}
        </div>
        <form v-else class="space-y-4 rounded-xl border bg-white p-5" @submit.prevent="save">
          <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
              <p class="text-xs text-slate-500">{{ t('academicStudentFile.fileOf') }}</p>
              <h3 class="text-lg font-semibold text-[var(--rdp-forest)]">{{ selected.full_name }}</h3>
              <p class="mt-1 text-sm text-slate-600">
                {{ t('academicStudents.age') }}: {{ ageText(formAge ?? selectedAge) }}
                · {{ t('academicStudents.level') }}: {{ label(selected.level) || '—' }}
              </p>
              <p class="mt-1 text-sm text-slate-600">
                {{ t('academicStudents.classCounselor') }}: {{ selected.class_counselor?.full_name || '—' }}
              </p>
              <p class="mt-1 text-sm text-slate-600">
                {{ t('academicStudents.classSupervisor') }}: {{ selected.class_supervisor?.full_name || '—' }}
                <span class="text-xs text-slate-500">({{ t('academicStudents.supervisorMonthly') }})</span>
              </p>
              <p v-if="selected.supervisor_last_visit?.visited_on" class="mt-1 text-xs text-slate-500">
                {{ t('academicStudents.lastSupervisorVisit') }}:
                {{ selected.supervisor_last_visit.visited_on }}
              </p>
            </div>
            <div class="flex flex-wrap gap-2">
            <button
              type="button"
              class="rounded border border-teal-800 px-4 py-2 text-sm font-semibold text-teal-800 disabled:opacity-60"
              :disabled="downloading"
              @click="downloadPdf"
            >
              {{ downloading ? t('academicStudents.downloading') : t('academicStudents.downloadPdf') }}
            </button>
            <template v-if="canViewExams">
              <RouterLink
                :to="`${base}/achievement/students/${selected.id}`"
                class="rounded border px-4 py-2 text-sm font-semibold text-[var(--rdp-forest)]"
              >
                {{ t('academicStudents.examResults') }}
              </RouterLink>
              <button
                type="button"
                class="rounded border px-4 py-2 text-sm font-semibold disabled:opacity-60"
                :disabled="downloadingResults"
                @click="downloadExamResultsPdf"
              >
                {{ downloadingResults ? t('academicStudents.downloading') : t('academicStudents.examResultsPdf') }}
              </button>
            </template>
            </div>
          </div>
          <div class="flex flex-wrap items-center gap-3">
            <img v-if="photoPreview" :src="photoPreview" alt="" class="h-24 w-24 rounded-xl object-cover object-top ring-2 ring-teal-800/20" />
            <div v-else class="flex h-24 w-24 items-center justify-center rounded-xl bg-teal-800 text-2xl font-bold text-white">
              {{ (selected.first_name || '?').slice(0, 1) }}
            </div>
            <div class="space-y-1 text-sm">
              <p class="text-xs text-slate-500">{{ t('academicStudents.photo') }}</p>
              <input ref="photoInput" type="file" accept="image/*" class="w-full text-xs" :disabled="!canUpdate" @change="onPhotoPick" />
              <button v-if="photoPreview && canUpdate" type="button" class="text-xs text-rose-700 hover:underline" @click="clearPhoto">
                {{ t('academicStudents.removePhoto') }}
              </button>
            </div>
          </div>
          <label class="block text-xs text-slate-500">
            {{ t('academicStudents.birthDate') }}
            <input v-model="form.birth_date" type="date" class="mt-1 w-full rounded border px-3 py-2 text-sm text-slate-800" :disabled="!canUpdate" />
          </label>
          <p v-if="formAge != null" class="text-sm text-slate-700">{{ t('academicStudents.age') }}: {{ formAge }} {{ t('academicStudents.years') }}</p>
          <select v-model="form.education_stage_id" class="w-full rounded border px-3 py-2 text-sm" :disabled="!canUpdate">
            <option value="">{{ t('academicStudents.stage') }}</option>
            <option v-for="stage in catalog.stages" :key="stage.id" :value="stage.id">{{ label(stage) }}</option>
          </select>
          <select v-model="form.level_id" class="w-full rounded border px-3 py-2 text-sm" :disabled="!canUpdate">
            <option value="">{{ t('academicStudents.level') }}</option>
            <option v-for="level in levels" :key="level.id" :value="level.id">{{ label(level) }}</option>
          </select>
          <label class="block text-xs text-slate-500">
            {{ t('academicStudents.counselorNotes') }}
            <textarea v-model="form.notes" rows="6" class="mt-1 w-full rounded border px-3 py-2 text-sm text-slate-800" :disabled="!canUpdate"></textarea>
          </label>
          <div v-if="canUpdate" class="flex flex-wrap gap-2">
            <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white" :disabled="saving">{{ t('forms.save') }}</button>
          </div>
        </form>
      </div>
    </template>
  </div>
</template>
