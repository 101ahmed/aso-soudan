<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { fetchExam, saveExamGrades } from '@/services/academic'
import { academicBaseFromPath } from '@/utils/academicPaths'
import { pickName } from '@/utils/localized'

const { t, locale } = useI18n()
const route = useRoute()
const base = computed(() => academicBaseFromPath(route.path))
const examId = computed(() => route.params.examId)

const loading = ref(true)
const saving = ref(false)
const error = ref('')
const success = ref('')
const payload = ref(null)
const rows = ref([])

const exam = computed(() => payload.value?.exam)
const canGrade = computed(() => Boolean(exam.value?.can_grade))

function label(item) {
  return pickName(item, locale.value)
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    payload.value = await fetchExam(examId.value)
    rows.value = (payload.value.roster || []).map((row) => ({
      student_id: row.student_id,
      full_name: row.full_name,
      score: row.score ?? '',
      is_absent: Boolean(row.is_absent),
      percent: row.percent,
      passed: row.passed,
    }))
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  error.value = ''
  success.value = ''
  try {
    payload.value = await saveExamGrades(examId.value, rows.value.map((row) => ({
      student_id: row.student_id,
      score: row.is_absent || row.score === '' ? null : Number(row.score),
      is_absent: Boolean(row.is_absent),
    })))
    rows.value = (payload.value.roster || []).map((row) => ({
      student_id: row.student_id,
      full_name: row.full_name,
      score: row.score ?? '',
      is_absent: Boolean(row.is_absent),
      percent: row.percent,
      passed: row.passed,
    }))
    success.value = t('academicExams.gradesSaved')
  } catch (e) {
    error.value = e.response?.data?.message || Object.values(e.response?.data?.errors || {})[0]?.[0] || e.message
  } finally {
    saving.value = false
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-4">
    <div>
      <RouterLink :to="`${base}/exams`" class="text-sm text-[var(--rdp-forest)] hover:underline">← {{ t('academicExams.back') }}</RouterLink>
      <h2 class="mt-1 text-lg font-semibold text-[var(--rdp-forest)]">{{ exam?.title || t('academicExams.sheet') }}</h2>
      <p v-if="exam" class="mt-1 text-sm text-slate-600">
        {{ label(exam.level) }} · {{ label(exam.subject) }} · {{ exam.exam_date }}
        · {{ t('academicExams.maxScore') }} {{ exam.max_score }}
        · {{ t('academicExams.passScore') }} {{ exam.pass_score }}
      </p>
    </div>
    <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
    <p v-if="success" class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ success }}</p>
    <p v-if="loading" class="text-sm text-slate-500">{{ t('academicExams.loading') }}</p>

    <div v-if="payload?.stats" class="grid gap-3 md:grid-cols-3">
      <div class="rounded-xl border bg-white p-4 text-sm">{{ t('academicExams.entered') }}: {{ payload.stats.entered }}/{{ payload.stats.students }}</div>
      <div class="rounded-xl border bg-white p-4 text-sm">{{ t('academicAchievement.classAverage') }}: {{ payload.stats.average ?? '—' }}%</div>
      <div class="rounded-xl border bg-white p-4 text-sm">{{ t('academicExams.passCount') }}: {{ payload.stats.pass_count }}</div>
    </div>

    <div class="overflow-hidden rounded-xl border bg-white">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-start text-xs text-slate-500">
          <tr>
            <th class="px-4 py-3 font-medium">{{ t('academicExams.student') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('academicExams.score') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('academicExams.absent') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('academicExams.percent') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('academicExams.result') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in rows" :key="row.student_id" class="border-t">
            <td class="px-4 py-3 font-medium">{{ row.full_name }}</td>
            <td class="px-4 py-3">
              <input
                v-model="row.score"
                type="number"
                min="0"
                step="0.01"
                class="w-24 rounded border px-2 py-1"
                :disabled="!canGrade || row.is_absent"
              />
            </td>
            <td class="px-4 py-3">
              <input v-model="row.is_absent" type="checkbox" :disabled="!canGrade" @change="row.is_absent && (row.score = '')" />
            </td>
            <td class="px-4 py-3">{{ row.percent ?? '—' }}</td>
            <td class="px-4 py-3">
              <span v-if="row.is_absent">{{ t('academicExams.absent') }}</span>
              <span v-else-if="row.passed === true" class="text-emerald-700">{{ t('academicExams.passed') }}</span>
              <span v-else-if="row.passed === false" class="text-rose-700">{{ t('academicExams.failed') }}</span>
              <span v-else>—</span>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <button
      v-if="canGrade"
      type="button"
      class="rounded-md bg-teal-800 px-4 py-2 text-sm text-white"
      :disabled="saving"
      @click="save"
    >
      {{ t('academicExams.saveGrades') }}
    </button>
    <p v-else class="text-sm text-slate-500">{{ t('academicExams.gradeForbidden') }}</p>
  </div>
</template>
