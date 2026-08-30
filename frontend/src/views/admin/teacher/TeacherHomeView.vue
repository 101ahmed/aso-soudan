<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { fetchAttendanceOverview, fetchTimetable } from '@/services/academic'
import { pickName } from '@/utils/localized'

const { t, locale } = useI18n()
const auth = useAuthStore()

const loading = ref(true)
const error = ref('')
const overview = ref(null)
const timetableItems = ref([])

const cards = computed(() => [
  {
    to: '/admin/teacher/students',
    label: t('teacherAdmin.students'),
    hint: t('teacherAdmin.studentsHint'),
    show: auth.hasPermission('student.view') || auth.user?.roles?.some((r) => r.code === 'TEACHER'),
  },
  {
    to: '/admin/teacher/attendance',
    label: t('teacherAdmin.attendance'),
    hint: t('teacherAdmin.attendanceHint'),
    show: auth.hasPermission('attendance.view'),
  },
  {
    to: '/admin/teacher/timetable',
    label: t('teacherAdmin.timetable'),
    hint: t('teacherAdmin.timetableHint'),
    show: auth.hasPermission('attendance.view'),
  },
].filter((card) => card.show))

function label(item) {
  return pickName(item, locale.value)
}

function weekdayName(day) {
  return t(`teacherAdmin.weekdays.${day}`)
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

const upcoming = computed(() => {
  if (timetableItems.value.length) return timetableItems.value
  return overview.value?.upcoming_sessions || []
})

const scheduleByDay = computed(() => {
  const slots = overview.value?.schedule || []
  return [6, 7, 1, 2, 3, 4, 5]
    .map((weekday) => ({
      weekday,
      slots: slots.filter((slot) => Number(slot.weekday) === weekday),
    }))
    .filter((day) => day.slots.length)
})

function sessionLink(item) {
  if (item.id) return `/admin/teacher/attendance/sessions/${item.id}`
  if (item.session_id) return `/admin/teacher/attendance/sessions/${item.session_id}`
  if (item.subject?.id && item.level?.id) {
    return `/admin/teacher/attendance/subjects/${item.subject.id}?levelId=${item.level.id}`
  }
  return '/admin/teacher/attendance'
}

onMounted(async () => {
  if (!auth.hasPermission('attendance.view')) {
    loading.value = false
    return
  }
  try {
    const [overviewResult, timetableResult] = await Promise.allSettled([
      fetchAttendanceOverview(),
      fetchTimetable(),
    ])
    if (overviewResult.status === 'fulfilled') {
      overview.value = overviewResult.value
    } else {
      const message = overviewResult.reason?.response?.data?.message || overviewResult.reason?.message || ''
      if (message && !/fileinfo|MIME type|SQLSTATE|Stack trace/i.test(message)) {
        error.value = message
      }
    }
    if (timetableResult.status === 'fulfilled') {
      timetableItems.value = timetableResult.value?.data || []
    }
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="space-y-5">
    <p class="text-sm text-slate-600">{{ t('teacherAdmin.homeIntro') }}</p>

    <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
    <p v-else-if="loading" class="text-sm text-slate-500">{{ t('academicAttendance.loading') }}</p>

    <div class="grid gap-4 md:grid-cols-3">
      <RouterLink
        v-for="card in cards"
        :key="card.to"
        :to="card.to"
        class="rounded-xl border border-slate-200 bg-white p-5 hover:border-teal-700/40"
      >
        <h2 class="font-semibold text-[var(--rdp-forest)]">{{ card.label }}</h2>
        <p class="mt-2 text-sm text-slate-600">{{ card.hint }}</p>
      </RouterLink>
    </div>

    <section class="overflow-hidden rounded-xl border bg-white">
      <div class="border-b px-4 py-3">
        <h2 class="font-semibold text-[var(--rdp-forest)]">{{ t('academicTimetable.title') }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ t('teacherAdmin.timetableHint') }}</p>
      </div>
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-start text-xs text-slate-500">
          <tr>
            <th class="px-4 py-3 font-medium">{{ t('academicAttendance.date') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('academicAttendance.time') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('academicTimetable.teacher') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('academicAttendance.subject') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('academicStudents.level') }}</th>
            <th class="px-4 py-3 font-medium">{{ t('teacherAdmin.room') }}</th>
            <th class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="item in upcoming" :key="item.id || `${item.class_group_id}-${item.session_date}-${item.starts_at}`" class="border-t">
            <td class="px-4 py-3">
              <p class="font-medium">{{ formatDate(item.session_date) }}</p>
              <p class="text-xs text-slate-500">{{ item.session_date }}</p>
            </td>
            <td class="px-4 py-3 font-medium">{{ item.starts_at }} – {{ item.ends_at }}</td>
            <td class="px-4 py-3">{{ item.teacher?.full_name || '—' }}</td>
            <td class="px-4 py-3">{{ label(item.subject) || item.class_name }}</td>
            <td class="px-4 py-3">{{ label(item.level) || '—' }}</td>
            <td class="px-4 py-3">{{ item.room || '—' }}</td>
            <td class="px-4 py-3 text-end">
              <RouterLink :to="sessionLink(item)" class="text-xs font-semibold text-[var(--rdp-forest)] hover:underline">
                {{ t('teacherAdmin.openSheet') }}
              </RouterLink>
            </td>
          </tr>
          <tr v-if="!loading && !upcoming.length">
            <td colspan="7" class="px-4 py-8 text-center text-slate-500">{{ t('teacherAdmin.emptyUpcoming') }}</td>
          </tr>
        </tbody>
      </table>
    </section>

    <section class="space-y-3">
      <div>
        <h2 class="font-semibold text-[var(--rdp-forest)]">{{ t('teacherAdmin.schedule') }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ t('teacherAdmin.scheduleHint') }}</p>
      </div>
      <p v-if="!loading && !scheduleByDay.length" class="rounded-xl border border-dashed bg-white px-4 py-6 text-center text-sm text-slate-500">
        {{ t('teacherAdmin.emptySchedule') }}
      </p>
      <article
        v-for="day in scheduleByDay"
        :key="day.weekday"
        class="overflow-hidden rounded-xl border bg-white"
      >
        <h3 class="border-b bg-slate-50 px-4 py-2 text-sm font-semibold text-slate-700">{{ weekdayName(day.weekday) }}</h3>
        <table class="min-w-full text-sm">
          <tbody>
            <tr v-for="slot in day.slots" :key="slot.id" class="border-t">
              <td class="px-4 py-3 font-medium">{{ slot.starts_at }} – {{ slot.ends_at }}</td>
              <td class="px-4 py-3">{{ label(slot.subject) || slot.class_name }}</td>
              <td class="px-4 py-3">{{ label(slot.level) || '—' }}</td>
              <td class="px-4 py-3 text-slate-500">{{ slot.room || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </article>
    </section>
  </div>
</template>
