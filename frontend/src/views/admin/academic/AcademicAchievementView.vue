<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  downloadAchievementPdf,
  downloadStudentAcademicReportPdf,
  fetchAchievement,
  fetchExamCatalog,
  saveAchievementGrades,
} from '@/services/academic'
import { academicBaseFromPath } from '@/utils/academicPaths'
import { pickName } from '@/utils/localized'

const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const base = computed(() => academicBaseFromPath(route.path))

const loading = ref(false)
const saving = ref(false)
const downloading = ref(false)
const downloadingStudentId = ref(null)
const error = ref('')
const success = ref('')
const catalog = ref({ academic_years: [], levels: [], periods: ['term1', 'term2', 'term3', 'annual'], current_year: null })
const report = ref(null)
const studentQuery = ref('')
const draft = ref({})
const passDraft = ref({})
const maxDraft = ref({})
const previousDraft = ref({})

const filters = reactive({
  academic_year_id: '',
  level_id: '',
  period: 'term1',
})

const scale = computed(() => {
  const subjects = report.value?.subjects || []
  if (!subjects.length) return Number(report.value?.scale) || 100
  const total = subjects.reduce((sum, subject) => sum + subjectMaxMark(subject), 0)
  return Math.round((total / subjects.length) * 100) / 100
})
const editable = computed(() => Boolean(report.value?.editable))

const filteredStudents = computed(() => {
  const rows = report.value?.students || []
  const q = studentQuery.value.trim().toLowerCase()
  if (!q) return rows
  return rows.filter((row) => String(row.full_name || '').toLowerCase().includes(q))
})

function studentResultPath(studentId) {
  return `${base.value}/achievement/students/${studentId}?period=${filters.period}`
}

function label(item) {
  return pickName(item, locale.value)
}

function cellKey(studentId, subjectId) {
  return `${studentId}:${subjectId}`
}

function subjectScore(row, subjectId) {
  const map = row?.subjects || {}
  const value = map[subjectId] ?? map[String(subjectId)]
  return value === undefined || value === null || value === '' ? '' : value
}

function subjectMaxMark(subject) {
  const raw = maxDraft.value[subject.id]
  const value = Number(raw)
  if (Number.isFinite(value) && value > 0) return value
  const fallback = Number(subject.max_score)
  return Number.isFinite(fallback) && fallback > 0 ? fallback : 100
}

function subjectPassMark(subject) {
  const raw = passDraft.value[subject.id]
  const value = Number(raw)
  if (Number.isFinite(value)) return value
  const fallback = Number(subject.pass_score)
  return Number.isFinite(fallback) ? fallback : Math.round(subjectMaxMark(subject) * 0.5 * 100) / 100
}

function isPassed(score, subject) {
  const value = Number(score)
  return Number.isFinite(value) && value >= subjectPassMark(subject)
}

function averagePassMark() {
  const subjects = report.value?.subjects || []
  if (!subjects.length) return 50
  const total = subjects.reduce((sum, subject) => sum + subjectPassMark(subject), 0)
  return Math.round((total / subjects.length) * 100) / 100
}

function draftAverage(row) {
  const scores = (report.value?.subjects || [])
    .map((subject) => Number(draft.value[cellKey(row.student_id, subject.id)]))
    .filter((value) => Number.isFinite(value))
  if (!scores.length) return row.average ?? '—'
  const avg = scores.reduce((sum, value) => sum + value, 0) / scores.length
  return Math.round(avg * 100) / 100
}

function draftTrend(row) {
  const current = Number(draftAverage(row))
  const previous = Number(previousDraft.value[row.student_id])
  if (!Number.isFinite(current) || !Number.isFinite(previous)) {
    return { label: '—', delta: null, kind: null }
  }
  const delta = Math.round((current - previous) * 100) / 100
  if (delta > 0) return { label: t('academicAchievement.trendUp'), delta, kind: 'up' }
  if (delta < 0) return { label: t('academicAchievement.trendDown'), delta, kind: 'down' }
  return { label: t('academicAchievement.trendSame'), delta: 0, kind: 'same' }
}

function hydrateDraft(payload) {
  const next = {}
  const nextPass = {}
  const nextMax = {}
  const nextPrevious = {}
  for (const subject of payload?.subjects || []) {
    nextMax[subject.id] = subject.max_score ?? 100
    nextPass[subject.id] = subject.pass_score ?? 50
  }
  for (const row of payload?.students || []) {
    nextPrevious[row.student_id] = row.previous_average ?? ''
    for (const subject of payload?.subjects || []) {
      next[cellKey(row.student_id, subject.id)] = subjectScore(row, subject.id)
    }
  }
  draft.value = next
  passDraft.value = nextPass
  maxDraft.value = nextMax
  previousDraft.value = nextPrevious
}

watch(report, (payload) => hydrateDraft(payload))

async function downloadPdf() {
  if (!filters.level_id) return
  downloading.value = true
  error.value = ''
  try {
    await downloadAchievementPdf({
      academic_year_id: filters.academic_year_id || undefined,
      level_id: filters.level_id,
      period: filters.period,
      locale: locale.value,
    })
  } catch (e) {
    error.value = e.response?.data?.message || t('academicAchievement.downloadFailed')
  } finally {
    downloading.value = false
  }
}

async function downloadStudentPdf(studentId) {
  downloadingStudentId.value = studentId
  error.value = ''
  try {
    await downloadStudentAcademicReportPdf(studentId, {
      locale: locale.value,
      period: filters.period,
    })
  } catch (e) {
    error.value = e.response?.data?.message || t('academicAchievement.downloadFailed')
  } finally {
    downloadingStudentId.value = null
  }
}

function openFirstMatch() {
  const first = filteredStudents.value[0]
  if (!first) {
    error.value = t('academicAchievement.noStudentMatch')
    return
  }
  router.push(studentResultPath(first.student_id))
}

async function load() {
  if (!filters.level_id) {
    report.value = null
    return
  }
  loading.value = true
  error.value = ''
  success.value = ''
  try {
    report.value = await fetchAchievement({
      academic_year_id: filters.academic_year_id || undefined,
      level_id: filters.level_id,
      period: filters.period,
    })
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

async function saveGrades() {
  if (!editable.value) return
  saving.value = true
  error.value = ''
  success.value = ''
  try {
    const grades = []
    for (const row of report.value?.students || []) {
      for (const subject of report.value?.subjects || []) {
        if (!subject.can_grade) continue
        const raw = draft.value[cellKey(row.student_id, subject.id)]
        grades.push({
          student_id: row.student_id,
          subject_id: subject.id,
          score: raw === '' || raw === null || raw === undefined ? null : Number(raw),
        })
      }
    }
    report.value = await saveAchievementGrades({
      academic_year_id: filters.academic_year_id || undefined,
      level_id: filters.level_id,
      period: filters.period,
      grades,
      pass_scores: (report.value?.subjects || [])
        .filter((subject) => subject.can_grade)
        .map((subject) => {
          const max = subjectMaxMark(subject)
          let pass = subjectPassMark(subject)
          if (pass > max) pass = Math.round(max * 0.5 * 100) / 100
          return { subject_id: subject.id, pass_score: pass }
        }),
      max_scores: (report.value?.subjects || [])
        .filter((subject) => subject.can_grade)
        .map((subject) => ({
          subject_id: subject.id,
          max_score: subjectMaxMark(subject),
        })),
      previous_averages: (report.value?.students || []).map((row) => {
        const raw = previousDraft.value[row.student_id]
        return {
          student_id: row.student_id,
          previous_average: raw === '' || raw === null || raw === undefined ? null : Number(raw),
        }
      }),
    })
    success.value = t('academicAchievement.gradesSaved')
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    saving.value = false
  }
}

onMounted(async () => {
  try {
    catalog.value = await fetchExamCatalog()
    filters.academic_year_id = catalog.value.current_year?.id || catalog.value.academic_years?.[0]?.id || ''
    filters.level_id = catalog.value.levels?.[0]?.id || ''
    if (filters.level_id) await load()
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
})
</script>

<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold text-[var(--rdp-forest)]">{{ t('academicAchievement.title') }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ t('academicAchievement.hint') }}</p>
      </div>
      <div class="flex flex-wrap gap-2">
        <button
          v-if="editable"
          type="button"
          class="rounded-md bg-[var(--rdp-forest)] px-3 py-2 text-sm text-white disabled:opacity-60"
          :disabled="saving || !filters.level_id"
          @click="saveGrades"
        >
          {{ saving ? t('academicAchievement.saving') : t('academicAchievement.saveGrades') }}
        </button>
        <button type="button" class="rounded-md border px-3 py-2 text-sm" :disabled="downloading || !filters.level_id" @click="downloadPdf">
          {{ downloading ? t('academicAchievement.downloading') : t('academicAchievement.downloadPdf') }}
        </button>
      </div>
    </div>
    <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
    <p v-if="success" class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">{{ success }}</p>

    <div class="flex flex-wrap gap-2">
      <select v-model="filters.academic_year_id" class="rounded-md border px-3 py-2 text-sm" @change="load">
        <option v-for="year in catalog.academic_years" :key="year.id" :value="year.id">{{ year.name }}</option>
      </select>
      <select v-model="filters.level_id" class="rounded-md border px-3 py-2 text-sm" @change="load">
        <option v-for="level in catalog.levels" :key="level.id" :value="level.id">{{ label(level) }}</option>
      </select>
      <select v-model="filters.period" class="rounded-md border px-3 py-2 text-sm" @change="load">
        <option v-for="period in catalog.periods" :key="period" :value="period">{{ t(`academicExams.periods.${period}`) }}</option>
      </select>
      <RouterLink :to="`${base}/exams`" class="rounded-md border px-3 py-2 text-sm">{{ t('academicExams.title') }}</RouterLink>
      <input
        v-model="studentQuery"
        type="search"
        class="min-w-[14rem] flex-1 rounded-md border px-3 py-2 text-sm"
        :placeholder="t('academicAchievement.searchStudent')"
        @keyup.enter="openFirstMatch"
      />
      <button type="button" class="rounded-md border px-3 py-2 text-sm" @click="openFirstMatch">
        {{ t('academicAchievement.findStudent') }}
      </button>
    </div>

    <div v-if="report" class="rounded-xl border bg-white p-4 text-sm">
      {{ t('academicAchievement.classAverage') }}:
      <strong>{{ report.class_average ?? '—' }} / {{ scale }}</strong>
      · {{ t('academicAchievement.yearGrade') }}:
      <strong>{{ report.class_year_average ?? '—' }} / {{ scale }}</strong>
      · {{ t('academicAchievement.passMark') }}:
      <strong>{{ averagePassMark() }} / {{ scale }}</strong>
      · {{ label(report.level) }}
    </div>

    <div class="overflow-x-auto rounded-xl border bg-white">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-start text-xs text-slate-500">
          <tr>
            <th class="px-4 py-3 font-medium">{{ t('academicExams.student') }}</th>
            <th v-for="subject in report?.subjects || []" :key="subject.id" class="px-4 py-3 font-medium">
              {{ label(subject) }}
              <label class="mt-2 flex flex-col gap-1 font-normal text-[11px] text-slate-600">
                {{ t('academicExams.maxScore') }}
                <input
                  v-if="editable && subject.can_grade"
                  v-model="maxDraft[subject.id]"
                  type="number"
                  min="0.01"
                  max="1000"
                  step="0.01"
                  class="w-24 rounded border px-2 py-1 text-sm text-slate-800"
                />
                <span v-else>{{ subject.max_score ?? scale }}</span>
              </label>
              <label class="mt-2 flex flex-col gap-1 font-normal text-[11px] text-slate-600">
                {{ t('academicAchievement.passMark') }}
                <input
                  v-if="editable && subject.can_grade"
                  v-model="passDraft[subject.id]"
                  type="number"
                  min="0"
                  :max="subjectMaxMark(subject)"
                  step="0.01"
                  class="w-24 rounded border px-2 py-1 text-sm text-slate-800"
                />
                <span v-else>{{ subject.pass_score ?? 50 }}</span>
              </label>
            </th>
            <th class="px-4 py-3 font-medium">{{ t('academicAchievement.studentAverage') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('academicAchievement.yearGrade') }}</th>
            <th class="px-4 py-3 font-medium">
              {{ t('academicAchievement.previous') }}
              <span class="mt-1 block font-normal text-[11px]">{{ t('academicAchievement.previousHint') }}</span>
            </th>
            <th class="px-4 py-3 font-medium">{{ t('academicAchievement.progress') }}</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in filteredStudents" :key="row.student_id" class="border-t">
            <td class="px-4 py-3 font-medium">{{ row.full_name }}</td>
            <td v-for="subject in report.subjects" :key="`${row.student_id}-${subject.id}`" class="px-4 py-3">
              <input
                v-if="editable && subject.can_grade"
                v-model="draft[cellKey(row.student_id, subject.id)]"
                type="number"
                min="0"
                :max="subjectMaxMark(subject)"
                step="0.01"
                class="w-24 rounded border px-2 py-1 text-sm"
                :class="isPassed(draft[cellKey(row.student_id, subject.id)], subject) ? 'border-emerald-300' : (draft[cellKey(row.student_id, subject.id)] === '' ? '' : 'border-rose-300')"
              />
              <span v-else>{{ subjectScore(row, subject.id) === '' ? '—' : subjectScore(row, subject.id) }}</span>
            </td>
            <td class="px-4 py-3 font-semibold">
              {{ draftAverage(row) }}
              <span v-if="draftAverage(row) !== '—'" class="ms-1 text-xs font-normal" :class="Number(draftAverage(row)) >= averagePassMark() ? 'text-emerald-700' : 'text-rose-700'">
                {{ Number(draftAverage(row)) >= averagePassMark() ? t('academicAchievement.passed') : t('academicAchievement.failed') }}
              </span>
            </td>
            <td class="px-4 py-3 font-semibold">
              {{ row.year_average ?? '—' }}
              <span v-if="row.year_average != null" class="ms-1 text-xs font-normal" :class="row.year_passed ? 'text-emerald-700' : 'text-rose-700'">
                {{ row.year_passed ? t('academicAchievement.passed') : t('academicAchievement.failed') }}
              </span>
              <span class="mt-1 block text-[11px] font-normal text-slate-500">
                {{ t('academicExams.periods.term1') }} {{ row.terms?.term1 ?? '—' }}
                · {{ t('academicExams.periods.term2') }} {{ row.terms?.term2 ?? '—' }}
                · {{ t('academicExams.periods.term3') }} {{ row.terms?.term3 ?? '—' }}
              </span>
            </td>
            <td class="px-4 py-3">
              <input
                v-if="editable"
                v-model="previousDraft[row.student_id]"
                type="number"
                min="0"
                :max="scale"
                step="0.01"
                class="w-24 rounded border px-2 py-1 text-sm"
                :placeholder="t('academicAchievement.previous')"
              />
              <span v-else>{{ row.previous_average ?? '—' }}</span>
            </td>
            <td class="px-4 py-3">
              <span
                class="font-medium"
                :class="{
                  'text-emerald-700': draftTrend(row).kind === 'up',
                  'text-rose-700': draftTrend(row).kind === 'down',
                }"
              >
                {{ draftTrend(row).label }}
              </span>
              <span v-if="draftTrend(row).delta != null" class="ms-1 text-xs text-slate-500">
                {{ draftTrend(row).delta > 0 ? '+' : '' }}{{ draftTrend(row).delta }}
              </span>
            </td>
            <td class="px-4 py-3 text-end space-x-2 space-x-reverse">
              <RouterLink
                :to="studentResultPath(row.student_id)"
                class="text-xs font-semibold text-[var(--rdp-forest)] hover:underline"
              >
                {{ t('academicAchievement.report') }}
              </RouterLink>
              <button
                type="button"
                class="text-xs text-slate-700 hover:underline"
                :disabled="downloadingStudentId === row.student_id"
                @click="downloadStudentPdf(row.student_id)"
              >
                {{ downloadingStudentId === row.student_id ? t('academicAchievement.downloading') : t('academicAchievement.downloadStudentPdf') }}
              </button>
            </td>
          </tr>
          <tr v-if="!loading && report && !filteredStudents.length">
            <td :colspan="(report.subjects?.length || 0) + 6" class="px-4 py-8 text-center text-slate-500">
              {{ studentQuery ? t('academicAchievement.noStudentMatch') : t('academicAchievement.empty') }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
