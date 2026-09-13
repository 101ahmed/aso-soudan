<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { downloadStudentAcademicReportPdf, fetchStudentAcademicReport } from '@/services/academic'
import { academicBaseFromPath } from '@/utils/academicPaths'
import { pickName } from '@/utils/localized'

const { t, locale } = useI18n()
const route = useRoute()
const base = computed(() => academicBaseFromPath(route.path))
const studentId = computed(() => route.params.studentId)
const period = computed(() => route.query.period || 'term1')

const loading = ref(true)
const error = ref('')
const report = ref(null)

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
  try {
    const { blob, contentType } = await downloadStudentAcademicReportPdf(studentId.value, {
      locale: locale.value,
      period: period.value,
    })
    const url = URL.createObjectURL(new Blob([blob], { type: contentType }))
    const link = document.createElement('a')
    link.href = url
    link.download = `academic-report-${studentId.value}.pdf`
    link.click()
    URL.revokeObjectURL(url)
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

onMounted(async () => {
  try {
    report.value = await fetchStudentAcademicReport(studentId.value, { period: period.value })
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <RouterLink :to="`${base}/achievement`" class="text-sm text-[var(--rdp-forest)] hover:underline">← {{ t('academicAchievement.title') }}</RouterLink>
        <h2 class="mt-1 text-lg font-semibold text-[var(--rdp-forest)]">{{ t('academicAchievement.reportTitle') }}</h2>
        <p v-if="report" class="mt-1 text-sm text-slate-600">{{ report.student?.full_name }} · {{ label(report.student?.level) }}</p>
      </div>
      <button type="button" class="rounded-md border px-3 py-2 text-sm" @click="downloadPdf">{{ t('academicAchievement.downloadPdf') }}</button>
    </div>
    <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
    <p v-if="loading" class="text-sm text-slate-500">{{ t('academicExams.loading') }}</p>

    <div v-if="report" class="grid gap-3 md:grid-cols-4">
      <div class="rounded-xl border bg-white p-4 text-sm">{{ t('academicAchievement.studentAverage') }}: <strong>{{ report.overall_average ?? '—' }}</strong></div>
      <div class="rounded-xl border bg-white p-4 text-sm">{{ t('academicAchievement.classAverage') }}: <strong>{{ report.class_average ?? '—' }}</strong></div>
      <div class="rounded-xl border bg-white p-4 text-sm">{{ t('academicAchievement.previous') }}: <strong>{{ report.previous_average ?? '—' }}</strong></div>
      <div class="rounded-xl border bg-white p-4 text-sm">{{ t('academicAchievement.progress') }}: <strong>{{ trendLabel(report.trend) }}</strong></div>
    </div>

    <section v-for="subject in report?.subjects || []" :key="subject.subject.id" class="overflow-hidden rounded-xl border bg-white">
      <div class="border-b px-4 py-3">
        <h3 class="font-semibold text-[var(--rdp-forest)]">{{ label(subject.subject) }}</h3>
        <p class="text-xs text-slate-500">{{ t('academicAchievement.studentAverage') }}: {{ subject.average ?? '—' }}</p>
      </div>
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-xs text-slate-500">
          <tr>
            <th class="px-4 py-2 font-medium">{{ t('academicExams.examTitle') }}</th>
            <th class="px-4 py-2 font-medium">{{ t('academicExams.date') }}</th>
            <th class="px-4 py-2 font-medium">{{ t('academicExams.score') }}</th>
            <th class="px-4 py-2 font-medium">{{ t('academicExams.percent') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="exam in subject.exams" :key="exam.id" class="border-t">
            <td class="px-4 py-2">{{ exam.title }}</td>
            <td class="px-4 py-2">{{ exam.exam_date }}</td>
            <td class="px-4 py-2">{{ exam.is_absent ? t('academicExams.absent') : (exam.score ?? '—') }} / {{ exam.max_score }}</td>
            <td class="px-4 py-2">{{ exam.percent ?? '—' }}</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="overflow-hidden rounded-xl border bg-white">
      <h3 class="border-b px-4 py-3 font-semibold text-[var(--rdp-forest)]">{{ t('academicAchievement.periods') }}</h3>
      <table class="min-w-full text-sm">
        <tbody>
          <tr v-for="(row, index) in report?.periods || []" :key="index" class="border-t">
            <td class="px-4 py-2">{{ row.year_name }}</td>
            <td class="px-4 py-2">{{ t(`academicExams.periods.${row.period}`) }}</td>
            <td class="px-4 py-2 font-semibold">{{ row.average ?? '—' }}</td>
          </tr>
        </tbody>
      </table>
    </section>
  </div>
</template>
