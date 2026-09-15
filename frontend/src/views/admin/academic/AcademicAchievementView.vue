<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import {
  downloadAchievementPdf,
  downloadStudentAcademicReportPdf,
  fetchAchievement,
  fetchExamCatalog,
} from '@/services/academic'
import { academicBaseFromPath } from '@/utils/academicPaths'
import { pickName } from '@/utils/localized'

const { t, locale } = useI18n()
const route = useRoute()
const router = useRouter()
const base = computed(() => academicBaseFromPath(route.path))

const loading = ref(false)
const downloading = ref(false)
const downloadingStudentId = ref(null)
const error = ref('')
const catalog = ref({ academic_years: [], levels: [], periods: ['term1', 'term2', 'term3', 'annual'], current_year: null })
const report = ref(null)
const studentQuery = ref('')

const filters = reactive({
  academic_year_id: '',
  level_id: '',
  period: 'term1',
})

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

function trendLabel(trend) {
  if (trend === 'up') return t('academicAchievement.trendUp')
  if (trend === 'down') return t('academicAchievement.trendDown')
  if (trend === 'same') return t('academicAchievement.trendSame')
  return '—'
}

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
      <button type="button" class="rounded-md border px-3 py-2 text-sm" :disabled="downloading || !filters.level_id" @click="downloadPdf">
        {{ downloading ? t('academicAchievement.downloading') : t('academicAchievement.downloadPdf') }}
      </button>
    </div>
    <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>

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
      <strong>{{ report.class_average ?? '—' }}</strong>
      · {{ label(report.level) }}
    </div>

    <div class="overflow-x-auto rounded-xl border bg-white">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-start text-xs text-slate-500">
          <tr>
            <th class="px-4 py-3 font-medium">{{ t('academicExams.student') }}</th>
            <th v-for="subject in report?.subjects || []" :key="subject.id" class="px-4 py-3 font-medium">{{ label(subject) }}</th>
            <th class="px-4 py-3 font-medium">{{ t('academicAchievement.studentAverage') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('academicAchievement.previous') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('academicAchievement.progress') }}</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in filteredStudents" :key="row.student_id" class="border-t">
            <td class="px-4 py-3 font-medium">{{ row.full_name }}</td>
            <td v-for="subject in report.subjects" :key="`${row.student_id}-${subject.id}`" class="px-4 py-3">
              {{ row.subjects?.[subject.id] ?? '—' }}
            </td>
            <td class="px-4 py-3 font-semibold">{{ row.average ?? '—' }}</td>
            <td class="px-4 py-3">{{ row.previous_average ?? '—' }}</td>
            <td class="px-4 py-3">{{ trendLabel(row.trend) }}</td>
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
            <td :colspan="(report.subjects?.length || 0) + 5" class="px-4 py-8 text-center text-slate-500">
              {{ studentQuery ? t('academicAchievement.noStudentMatch') : t('academicAchievement.empty') }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
