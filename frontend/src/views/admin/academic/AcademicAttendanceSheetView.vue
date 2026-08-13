<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { fetchAttendanceSheet, saveAttendanceSheet } from '@/services/academic'
import { attendanceBaseFromPath } from '@/utils/academicPaths'

const route = useRoute()
const { t, locale } = useI18n()
const auth = useAuthStore()
const attendanceBase = computed(() => attendanceBaseFromPath(route.path))

const session = ref(null)
const rows = ref([])
const statuses = ref(['present', 'absent', 'late', 'excused'])
const error = ref('')
const success = ref('')
const saving = ref(false)
const canEdit = computed(() => auth.hasPermission('attendance.create'))

const subjectLabel = computed(() => {
  const s = session.value?.class_group?.subject
  if (!s) return ''
  return locale.value === 'ar' ? s.name_ar : s.name_fr
})

const counts = computed(() => {
  const c = { present: 0, absent: 0, late: 0, excused: 0 }
  for (const row of rows.value) {
    if (c[row.status] != null) c[row.status] += 1
  }
  return c
})

function statusClass(status) {
  return {
    present: 'border-emerald-300 bg-emerald-50 text-emerald-800',
    absent: 'border-rose-300 bg-rose-50 text-rose-800',
    late: 'border-amber-300 bg-amber-50 text-amber-800',
    excused: 'border-sky-300 bg-sky-50 text-sky-800',
  }[status] || 'border-slate-200'
}

async function load() {
  error.value = ''
  try {
    const data = await fetchAttendanceSheet(route.params.sessionId)
    session.value = data.session
    rows.value = (data.rows || []).map((r) => ({ ...r }))
    if (data.statuses?.length) statuses.value = data.statuses
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

async function save() {
  saving.value = true
  success.value = ''
  error.value = ''
  try {
    const data = await saveAttendanceSheet(
      route.params.sessionId,
      rows.value.map((r) => ({
        student_id: r.student_id,
        status: r.status,
        notes: r.notes || null,
      })),
    )
    session.value = data.session
    rows.value = (data.rows || []).map((r) => ({ ...r }))
    success.value = t('academicAttendance.saved')
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    saving.value = false
  }
}

function markAll(status) {
  rows.value = rows.value.map((r) => ({ ...r, status }))
}

onMounted(load)
</script>

<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <RouterLink
          v-if="session?.class_group?.subject_id"
          :to="`${attendanceBase}/subjects/${session.class_group.subject_id}`"
          class="text-xs text-[var(--rdp-forest)] hover:underline"
        >
          ← {{ t('academicAttendance.back') }}
        </RouterLink>
        <h2 class="mt-1 text-lg font-semibold text-[var(--rdp-forest)]">{{ t('academicAttendance.sheet') }}</h2>
        <p class="text-sm text-slate-600">
          {{ subjectLabel }}
          <span v-if="session"> · {{ session.session_date }} · {{ session.starts_at }}–{{ session.ends_at }}</span>
        </p>
      </div>
      <div v-if="canEdit" class="flex flex-wrap gap-2">
        <button type="button" class="rounded border px-2 py-1 text-xs" @click="markAll('present')">{{ t('academicAttendance.markAllPresent') }}</button>
        <button
          type="button"
          class="rounded bg-teal-800 px-3 py-1.5 text-sm text-white disabled:opacity-50"
          :disabled="saving"
          @click="save"
        >
          {{ saving ? t('academicAttendance.saving') : t('forms.save') }}
        </button>
      </div>
    </div>

    <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
    <p v-if="success" class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ success }}</p>

    <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
      <div class="rounded-lg border bg-white px-3 py-2 text-center text-sm">
        <p class="text-xs text-slate-500">{{ t('academicAttendance.present') }}</p>
        <p class="font-semibold text-emerald-700">{{ counts.present }}</p>
      </div>
      <div class="rounded-lg border bg-white px-3 py-2 text-center text-sm">
        <p class="text-xs text-slate-500">{{ t('academicAttendance.absent') }}</p>
        <p class="font-semibold text-rose-700">{{ counts.absent }}</p>
      </div>
      <div class="rounded-lg border bg-white px-3 py-2 text-center text-sm">
        <p class="text-xs text-slate-500">{{ t('academicAttendance.late') }}</p>
        <p class="font-semibold text-amber-700">{{ counts.late }}</p>
      </div>
      <div class="rounded-lg border bg-white px-3 py-2 text-center text-sm">
        <p class="text-xs text-slate-500">{{ t('academicAttendance.excused') }}</p>
        <p class="font-semibold text-sky-700">{{ counts.excused }}</p>
      </div>
    </div>

    <div class="overflow-hidden rounded-xl border bg-white">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-xs text-slate-500">
          <tr>
            <th class="px-4 py-3 text-start font-medium">#</th>
            <th class="px-4 py-3 text-start font-medium">{{ t('academicAttendance.student') }}</th>
            <th class="px-4 py-3 text-start font-medium">{{ t('academicAttendance.status') }}</th>
            <th class="px-4 py-3 text-start font-medium">{{ t('academicAttendance.notes') }}</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, index) in rows" :key="row.student_id" class="border-t">
            <td class="px-4 py-3 text-slate-400">{{ index + 1 }}</td>
            <td class="px-4 py-3 font-medium">{{ row.full_name }}</td>
            <td class="px-4 py-3">
              <select
                v-model="row.status"
                class="rounded border px-2 py-1.5 text-sm"
                :class="statusClass(row.status)"
                :disabled="!canEdit"
              >
                <option v-for="st in statuses" :key="st" :value="st">
                  {{ t(`academicAttendance.statuses.${st}`) }}
                </option>
              </select>
            </td>
            <td class="px-4 py-3">
              <input
                v-model="row.notes"
                class="w-full rounded border px-2 py-1.5 text-sm"
                :disabled="!canEdit"
                :placeholder="t('academicAttendance.notes')"
              />
            </td>
          </tr>
          <tr v-if="!rows.length">
            <td colspan="4" class="px-4 py-8 text-center text-slate-500">{{ t('academicAttendance.noStudents') }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
