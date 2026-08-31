<script setup>
import { computed, onMounted, onActivated, reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  deleteClassSession,
  fetchAttendanceSheet,
  updateClassSession,
} from '@/services/academic'
import { attendanceBaseFromPath } from '@/utils/academicPaths'
import { pickName } from '@/utils/localized'
import AttendanceRosterTable from '@/components/admin/AttendanceRosterTable.vue'

const route = useRoute()
const router = useRouter()
const { t, locale } = useI18n()
const auth = useAuthStore()
const attendanceBase = computed(() => attendanceBaseFromPath(route.path))

const session = ref(null)
const error = ref('')
const success = ref('')
const canEdit = computed(() => (
  auth.hasPermission('attendance.create')
  || auth.hasPermission('attendance.update')
  || auth.hasPermission('attendance.delete')
))

const sessionForm = reactive({
  session_date: '',
  starts_at: '',
  ends_at: '',
  room: '',
})

const classId = computed(() => session.value?.class_group?.id || session.value?.class_group_id || null)

const subjectLabel = computed(() => {
  const s = session.value?.class_group?.subject
  if (!s) return ''
  return pickName(s, locale.value)
})

const backTo = computed(() => {
  const subjectId = session.value?.class_group?.subject_id
  return subjectId ? `${attendanceBase.value}/subjects/${subjectId}` : attendanceBase.value
})

function applySession(item) {
  session.value = item
  sessionForm.session_date = (item?.session_date || '').slice(0, 10)
  sessionForm.starts_at = String(item?.starts_at || '').slice(0, 5)
  sessionForm.ends_at = String(item?.ends_at || '').slice(0, 5)
  sessionForm.room = item?.room || ''
}

async function load() {
  error.value = ''
  try {
    const data = await fetchAttendanceSheet(route.params.sessionId)
    applySession(data.session)
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

async function saveSessionMeta() {
  error.value = ''
  success.value = ''
  try {
    await updateClassSession(route.params.sessionId, { ...sessionForm })
    await load()
    success.value = t('academicAttendance.sessionUpdated')
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

async function removeSession() {
  if (!confirm(t('academicAttendance.confirmDeleteSession'))) return
  try {
    await deleteClassSession(route.params.sessionId)
    router.push(backTo.value)
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

onMounted(load)
onActivated(load)
</script>

<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <RouterLink :to="backTo" class="text-xs text-[var(--rdp-forest)] hover:underline">
          ← {{ t('academicAttendance.back') }}
        </RouterLink>
        <h2 class="mt-1 text-lg font-semibold text-[var(--rdp-forest)]">{{ t('academicAttendance.sheet') }}</h2>
        <p class="text-sm text-slate-600">
          {{ subjectLabel }}
          <span v-if="session"> · {{ session.session_date }} · {{ session.starts_at }}–{{ session.ends_at }}</span>
        </p>
      </div>
      <button
        v-if="canEdit"
        type="button"
        class="rounded border border-rose-300 px-3 py-1.5 text-sm text-rose-700"
        @click="removeSession"
      >
        {{ t('academicAttendance.deleteSession') }}
      </button>
    </div>

    <form
      v-if="canEdit && session"
      class="grid gap-3 rounded-xl border bg-white p-4 sm:grid-cols-2 lg:grid-cols-5"
      @submit.prevent="saveSessionMeta"
    >
      <input v-model="sessionForm.session_date" type="date" required class="rounded border px-3 py-2 text-sm" />
      <input v-model="sessionForm.starts_at" type="time" required class="rounded border px-3 py-2 text-sm" />
      <input v-model="sessionForm.ends_at" type="time" required class="rounded border px-3 py-2 text-sm" />
      <input v-model="sessionForm.room" :placeholder="t('academicAttendance.room')" class="rounded border px-3 py-2 text-sm" />
      <button type="submit" class="rounded border border-teal-800 px-3 py-2 text-sm text-teal-800">
        {{ t('academicAttendance.saveSession') }}
      </button>
    </form>

    <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
    <p v-if="success" class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ success }}</p>

    <AttendanceRosterTable
      v-if="classId"
      :class-id="classId"
      :session-id="route.params.sessionId"
      :can-manage="canEdit"
      @error="error = $event"
    />
  </div>
</template>
