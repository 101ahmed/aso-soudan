<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { fetchAttendanceOverview } from '@/services/academic'
import { attendanceBaseFromPath } from '@/utils/academicPaths'
import { pickName } from '@/utils/localized'

const { t, locale } = useI18n()
const auth = useAuthStore()
const route = useRoute()
const loading = ref(true)
const error = ref('')
const overview = ref(null)
const openSubjectId = ref(null)
const attendanceBase = computed(() => attendanceBaseFromPath(route.path))
const canView = computed(() => auth.hasPermission('attendance.view'))

let pollId = 0

function subjectName(item) {
  return pickName(item, locale.value)
}

function rateStyle(rate) {
  if (rate == null) return 'bg-slate-200'
  if (rate >= 85) return 'bg-emerald-500'
  if (rate >= 70) return 'bg-amber-500'
  return 'bg-rose-500'
}

function share(count, recorded) {
  if (!recorded) return 0
  return Math.round((count / recorded) * 100)
}

async function load({ silent = false } = {}) {
  if (!canView.value) {
    loading.value = false
    return
  }
  if (!silent) error.value = ''
  try {
    overview.value = await fetchAttendanceOverview()
  } catch (e) {
    if (!silent) error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

function startLive() {
  stopLive()
  pollId = window.setInterval(() => {
    if (document.visibilityState === 'visible') load({ silent: true })
  }, 8000)
}

function stopLive() {
  if (pollId) {
    window.clearInterval(pollId)
    pollId = 0
  }
}

function onVisibility() {
  if (document.visibilityState === 'visible') load({ silent: true })
}

onMounted(() => {
  load()
  startLive()
  document.addEventListener('visibilitychange', onVisibility)
})

onBeforeUnmount(() => {
  stopLive()
  document.removeEventListener('visibilitychange', onVisibility)
})
</script>

<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold text-[var(--rdp-forest)]">{{ t('academicAttendance.title') }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ t('academicAttendance.subtitle') }}</p>
      </div>
      <p v-if="overview?.generated_at" class="text-xs text-slate-500">
        {{ t('academicAttendance.live') }}
        · {{ new Date(overview.generated_at).toLocaleTimeString() }}
      </p>
    </div>

    <p v-if="!canView" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
      {{ t('academicAttendance.forbidden') }}
    </p>
    <p v-else-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
    <p v-else-if="loading" class="text-sm text-slate-500">{{ t('academicAttendance.loading') }}</p>

    <template v-else-if="overview">
      <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <article class="rounded-xl border bg-white p-4">
          <p class="text-xs text-slate-500">{{ t('academicAttendance.year') }}</p>
          <p class="mt-1 text-lg font-semibold">{{ overview.academic_year?.name || '—' }}</p>
        </article>
        <article class="rounded-xl border bg-white p-4">
          <p class="text-xs text-slate-500">{{ t('academicAttendance.students') }}</p>
          <p class="mt-1 text-lg font-semibold">{{ overview.students_count }}</p>
        </article>
        <article class="rounded-xl border bg-white p-4">
          <p class="text-xs text-slate-500">{{ t('academicAttendance.present') }}</p>
          <p class="mt-1 text-lg font-semibold text-emerald-700">{{ overview.totals?.present_count || 0 }}</p>
        </article>
        <article class="rounded-xl border bg-white p-4">
          <p class="text-xs text-slate-500">{{ t('academicAttendance.absent') }}</p>
          <p class="mt-1 text-lg font-semibold text-rose-700">{{ overview.totals?.absent_count || 0 }}</p>
        </article>
        <article class="rounded-xl border bg-white p-4">
          <p class="text-xs text-slate-500">{{ t('academicAttendance.rate') }}</p>
          <p class="mt-1 text-lg font-semibold">
            {{ overview.totals?.attendance_rate != null ? `${overview.totals.attendance_rate}%` : '—' }}
          </p>
        </article>
      </div>

      <p v-if="!overview.totals?.recorded_count" class="rounded-xl border border-dashed bg-white px-4 py-6 text-center text-sm text-slate-500">
        {{ t('academicAttendance.noRecords') }}
      </p>

      <div class="space-y-3">
        <article
          v-for="subject in overview.subjects"
          :key="subject.id"
          class="overflow-hidden rounded-xl border bg-white"
        >
          <div class="flex flex-wrap items-center gap-3 px-4 py-3">
            <div class="min-w-0 flex-1">
              <p class="font-semibold">{{ subjectName(subject) }}</p>
              <p class="text-xs text-slate-500">
                {{ subject.classes_count }} {{ t('academicAttendance.classes') }}
                · {{ subject.sessions_count }} {{ t('academicAttendance.sessions') }}
                · {{ subject.students_count }} {{ t('academicAttendance.students') }}
                <span v-if="subject.last_session_date">
                  · {{ t('academicAttendance.lastSession') }} {{ subject.last_session_date }}
                </span>
              </p>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-sm">
              <span class="text-emerald-700">{{ subject.present_count }} {{ t('academicAttendance.present') }}</span>
              <span class="text-rose-700">{{ subject.absent_count }} {{ t('academicAttendance.absent') }}</span>
              <span class="font-semibold">
                {{ subject.attendance_rate != null ? `${subject.attendance_rate}%` : '—' }}
              </span>
              <button
                type="button"
                class="rounded border px-2 py-1 text-xs"
                @click="openSubjectId = openSubjectId === subject.id ? null : subject.id"
              >
                {{ t('academicAttendance.details') }}
              </button>
              <RouterLink
                :to="`${attendanceBase}/subjects/${subject.id}`"
                class="text-xs font-semibold text-[var(--rdp-forest)] hover:underline"
              >
                {{ t('academicAttendance.open') }}
              </RouterLink>
            </div>
          </div>
          <div class="h-2 bg-slate-100">
            <div class="flex h-full overflow-hidden">
              <div class="bg-emerald-500" :style="{ width: `${share(subject.present_count, subject.recorded_count)}%` }" />
              <div class="bg-amber-400" :style="{ width: `${share(subject.late_count, subject.recorded_count)}%` }" />
              <div class="bg-sky-400" :style="{ width: `${share(subject.excused_count, subject.recorded_count)}%` }" />
              <div class="bg-rose-500" :style="{ width: `${share(subject.absent_count, subject.recorded_count)}%` }" />
            </div>
          </div>
          <div v-if="openSubjectId === subject.id" class="border-t">
            <table class="min-w-full text-sm">
              <thead class="bg-slate-50 text-start text-xs text-slate-500">
                <tr>
                  <th class="px-4 py-2 font-medium">{{ t('academicAttendance.student') }}</th>
                  <th class="px-4 py-2 font-medium">{{ t('academicAttendance.present') }}</th>
                  <th class="px-4 py-2 font-medium">{{ t('academicAttendance.absent') }}</th>
                  <th class="px-4 py-2 font-medium">{{ t('academicAttendance.late') }}</th>
                  <th class="px-4 py-2 font-medium">{{ t('academicAttendance.excused') }}</th>
                  <th class="px-4 py-2 font-medium">{{ t('academicAttendance.rate') }}</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="student in subject.students" :key="student.id" class="border-t">
                  <td class="px-4 py-2 font-medium">{{ student.full_name }}</td>
                  <td class="px-4 py-2 text-emerald-700">{{ student.present_count }}</td>
                  <td class="px-4 py-2 text-rose-700">{{ student.absent_count }}</td>
                  <td class="px-4 py-2 text-amber-700">{{ student.late_count }}</td>
                  <td class="px-4 py-2 text-sky-700">{{ student.excused_count }}</td>
                  <td class="px-4 py-2">
                    <span
                      class="inline-block rounded-full px-2 py-0.5 text-xs text-white"
                      :class="rateStyle(student.attendance_rate)"
                    >
                      {{ student.attendance_rate != null ? `${student.attendance_rate}%` : '—' }}
                    </span>
                  </td>
                </tr>
                <tr v-if="!subject.students?.length">
                  <td colspan="6" class="px-4 py-6 text-center text-slate-500">{{ t('academicAttendance.noStudents') }}</td>
                </tr>
              </tbody>
            </table>
          </div>
        </article>
      </div>
    </template>
  </div>
</template>
