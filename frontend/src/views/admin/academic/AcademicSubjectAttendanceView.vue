<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  createClassSession,
  fetchClassSessions,
  fetchClassesBySubject,
} from '@/services/academic'
import { attendanceBaseFromPath } from '@/utils/academicPaths'

const route = useRoute()
const router = useRouter()
const { t, locale } = useI18n()
const auth = useAuthStore()
const attendanceBase = computed(() => attendanceBaseFromPath(route.path))

const subjectId = computed(() => route.params.subjectId)
const subject = ref(null)
const classes = ref([])
const selectedClassId = ref(null)
const sessions = ref([])
const error = ref('')
const canCreate = computed(() => auth.hasPermission('attendance.create'))

const form = reactive({
  session_date: new Date().toISOString().slice(0, 10),
  starts_at: '10:00',
  ends_at: '11:00',
  room: '',
})

function label(item) {
  if (!item) return ''
  return locale.value === 'ar' ? item.name_ar || item.name : item.name_fr || item.name
}

async function loadClasses() {
  error.value = ''
  try {
    const data = await fetchClassesBySubject(subjectId.value)
    subject.value = data.subject
    classes.value = data.data || []
    if (!selectedClassId.value && classes.value[0]) {
      selectedClassId.value = classes.value[0].id
    }
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

async function loadSessions() {
  if (!selectedClassId.value) {
    sessions.value = []
    return
  }
  try {
    const data = await fetchClassSessions(selectedClassId.value)
    sessions.value = data.data || []
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

async function createSession() {
  try {
    const session = await createClassSession(selectedClassId.value, { ...form })
    await loadSessions()
    router.push(`${attendanceBase.value}/sessions/${session.id}`)
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

watch(selectedClassId, loadSessions)
onMounted(async () => {
  await loadClasses()
  await loadSessions()
})
</script>

<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <RouterLink :to="attendanceBase" class="text-xs text-[var(--rdp-forest)] hover:underline">
          ← {{ t('academicAttendance.back') }}
        </RouterLink>
        <h2 class="mt-1 text-lg font-semibold text-[var(--rdp-forest)]">{{ label(subject) }}</h2>
        <p class="text-sm text-slate-600">{{ t('academicAttendance.bySubjectHint') }}</p>
      </div>
    </div>

    <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>

    <div class="grid gap-4 lg:grid-cols-[240px_1fr]">
      <aside class="space-y-2 rounded-xl border bg-white p-3">
        <p class="px-1 text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ t('academicAttendance.classes') }}</p>
        <button
          v-for="c in classes"
          :key="c.id"
          type="button"
          class="w-full rounded-lg px-3 py-2 text-start text-sm"
          :class="selectedClassId === c.id ? 'bg-teal-800 text-white' : 'hover:bg-slate-50'"
          @click="selectedClassId = c.id"
        >
          <span class="font-medium">{{ c.name }}</span>
          <span class="mt-0.5 block text-xs opacity-80">{{ c.students_count }} {{ t('academicAttendance.students') }}</span>
        </button>
      </aside>

      <div class="space-y-4">
        <form
          v-if="canCreate && selectedClassId"
          class="grid gap-3 rounded-xl border bg-white p-4 sm:grid-cols-2 lg:grid-cols-5"
          @submit.prevent="createSession"
        >
          <input v-model="form.session_date" type="date" required class="rounded border px-3 py-2 text-sm" />
          <input v-model="form.starts_at" type="time" required class="rounded border px-3 py-2 text-sm" />
          <input v-model="form.ends_at" type="time" required class="rounded border px-3 py-2 text-sm" />
          <input v-model="form.room" :placeholder="t('academicAttendance.room')" class="rounded border px-3 py-2 text-sm" />
          <button type="submit" class="rounded bg-teal-800 px-3 py-2 text-sm text-white">
            {{ t('academicAttendance.newSession') }}
          </button>
        </form>

        <div class="overflow-hidden rounded-xl border bg-white">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-xs text-slate-500">
              <tr>
                <th class="px-4 py-3 text-start font-medium">{{ t('academicAttendance.date') }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ t('academicAttendance.time') }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ t('academicAttendance.present') }}</th>
                <th class="px-4 py-3 text-start font-medium">{{ t('academicAttendance.absent') }}</th>
                <th class="px-4 py-3"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="s in sessions" :key="s.id" class="border-t">
                <td class="px-4 py-3">{{ (s.session_date || '').slice(0, 10) }}</td>
                <td class="px-4 py-3">{{ String(s.starts_at).slice(0, 5) }} – {{ String(s.ends_at).slice(0, 5) }}</td>
                <td class="px-4 py-3 text-emerald-700">{{ s.present_count }}</td>
                <td class="px-4 py-3 text-rose-700">{{ s.absent_count }}</td>
                <td class="px-4 py-3 text-end">
                  <RouterLink
                    :to="`${attendanceBase}/sessions/${s.id}`"
                    class="text-xs font-semibold text-[var(--rdp-forest)] hover:underline"
                  >
                    {{ t('academicAttendance.sheet') }}
                  </RouterLink>
                </td>
              </tr>
              <tr v-if="!sessions.length">
                <td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ t('academicAttendance.noSessions') }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</template>
