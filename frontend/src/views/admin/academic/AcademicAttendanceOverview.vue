<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { fetchAttendanceOverview } from '@/services/academic'

const { t, locale } = useI18n()
const auth = useAuthStore()
const loading = ref(true)
const error = ref('')
const overview = ref(null)

const canView = computed(() => auth.hasPermission('attendance.view'))

function subjectName(item) {
  return locale.value === 'ar' ? item.name_ar : item.name_fr
}

onMounted(async () => {
  if (!canView.value) {
    loading.value = false
    return
  }
  try {
    overview.value = await fetchAttendanceOverview()
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div class="space-y-5">
    <div>
      <h2 class="text-lg font-semibold text-[var(--rdp-forest)]">{{ t('academicAttendance.title') }}</h2>
      <p class="mt-1 text-sm text-slate-600">{{ t('academicAttendance.subtitle') }}</p>
    </div>

    <p v-if="!canView" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
      {{ t('academicAttendance.forbidden') }}
    </p>
    <p v-else-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
    <p v-else-if="loading" class="text-sm text-slate-500">…</p>

    <template v-else-if="overview">
      <div class="grid gap-3 sm:grid-cols-3">
        <article class="rounded-xl border bg-white p-4">
          <p class="text-xs text-slate-500">{{ t('academicAttendance.year') }}</p>
          <p class="mt-1 text-lg font-semibold">{{ overview.academic_year?.name || '—' }}</p>
        </article>
        <article class="rounded-xl border bg-white p-4">
          <p class="text-xs text-slate-500">{{ t('academicAttendance.students') }}</p>
          <p class="mt-1 text-lg font-semibold">{{ overview.students_count }}</p>
        </article>
        <article class="rounded-xl border bg-white p-4">
          <p class="text-xs text-slate-500">{{ t('academicAttendance.subjects') }}</p>
          <p class="mt-1 text-lg font-semibold">{{ overview.subjects?.length || 0 }}</p>
        </article>
      </div>

      <div class="overflow-hidden rounded-xl border bg-white">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-start text-xs text-slate-500">
            <tr>
              <th class="px-4 py-3 font-medium">{{ t('academicAttendance.subject') }}</th>
              <th class="px-4 py-3 font-medium">{{ t('academicAttendance.classes') }}</th>
              <th class="px-4 py-3 font-medium">{{ t('academicAttendance.sessions') }}</th>
              <th class="px-4 py-3 font-medium">{{ t('academicAttendance.present') }}</th>
              <th class="px-4 py-3 font-medium">{{ t('academicAttendance.absent') }}</th>
              <th class="px-4 py-3 font-medium"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="subject in overview.subjects" :key="subject.id" class="border-t">
              <td class="px-4 py-3 font-medium">{{ subjectName(subject) }}</td>
              <td class="px-4 py-3">{{ subject.classes_count }}</td>
              <td class="px-4 py-3">{{ subject.sessions_count }}</td>
              <td class="px-4 py-3 text-emerald-700">{{ subject.present_count }}</td>
              <td class="px-4 py-3 text-rose-700">{{ subject.absent_count }}</td>
              <td class="px-4 py-3 text-end">
                <RouterLink
                  :to="`/admin/secretariats/academic/attendance/subjects/${subject.id}`"
                  class="text-xs font-semibold text-[var(--rdp-forest)] hover:underline"
                >
                  {{ t('academicAttendance.open') }}
                </RouterLink>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </template>
  </div>
</template>
