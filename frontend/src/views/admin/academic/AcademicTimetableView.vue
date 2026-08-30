<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  createTimetableEntry,
  deleteTimetableEntry,
  fetchLevels,
  fetchSubjects,
  fetchTeachers,
  fetchTimetable,
  updateTimetableEntry,
} from '@/services/academic'
import { pickName } from '@/utils/localized'

const { t, locale } = useI18n()
const auth = useAuthStore()

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const items = ref([])
const teachers = ref([])
const subjects = ref([])
const levels = ref([])
const editingId = ref(null)
const yearName = ref('')

const form = reactive({
  teacher_id: '',
  subject_id: '',
  level_id: '',
  session_date: new Date().toISOString().slice(0, 10),
  starts_at: '10:00',
  ends_at: '11:00',
  room: '',
})

const canView = computed(() => auth.hasPermission('attendance.view'))
const canWrite = computed(() => {
  const roles = auth.user?.roles || []
  const isTeacherOnly = roles.some((role) => role.code === 'TEACHER')
    && !roles.some((role) => ['SUPER_ADMIN', 'ACADEMIC_SECRETARIAT'].includes(role.code))
  return auth.hasPermission('attendance.create') && !isTeacherOnly
})

function label(item) {
  return pickName(item, locale.value)
}

const selectedTeacher = computed(() =>
  teachers.value.find((teacher) => String(teacher.id) === String(form.teacher_id)),
)

const offeredSubjects = computed(() => {
  const taught = selectedTeacher.value?.subjects
  return taught?.length ? taught : subjects.value
})

watch(() => form.teacher_id, () => {
  if (!offeredSubjects.value.some((subject) => String(subject.id) === String(form.subject_id))) {
    form.subject_id = ''
  }
})

function resetForm() {
  editingId.value = null
  Object.assign(form, {
    teacher_id: '',
    subject_id: '',
    level_id: '',
    session_date: new Date().toISOString().slice(0, 10),
    starts_at: '10:00',
    ends_at: '11:00',
    room: '',
  })
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

async function load() {
  if (!canView.value) return
  loading.value = true
  error.value = ''
  try {
    const response = await fetchTimetable()
    items.value = response.data || []
    yearName.value = response.academic_year?.name || ''
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

function edit(item) {
  editingId.value = item.id
  Object.assign(form, {
    teacher_id: item.teacher_id || item.teacher?.id || '',
    subject_id: item.subject_id || item.subject?.id || '',
    level_id: item.level_id || item.level?.id || '',
    session_date: item.session_date || '',
    starts_at: item.starts_at || '10:00',
    ends_at: item.ends_at || '11:00',
    room: item.room || '',
  })
}

async function save() {
  if (!canWrite.value) return
  saving.value = true
  error.value = ''
  try {
    const payload = {
      teacher_id: Number(form.teacher_id),
      subject_id: Number(form.subject_id),
      level_id: Number(form.level_id),
      session_date: form.session_date,
      starts_at: String(form.starts_at).slice(0, 5),
      ends_at: String(form.ends_at).slice(0, 5),
      room: form.room || null,
    }
    if (editingId.value) await updateTimetableEntry(editingId.value, payload)
    else await createTimetableEntry(payload)
    resetForm()
    await load()
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || e.message
  } finally {
    saving.value = false
  }
}

async function remove(item) {
  if (!canWrite.value) return
  if (!confirm(t('academicTimetable.confirmDelete'))) return
  try {
    await deleteTimetableEntry(item.id)
    if (editingId.value === item.id) resetForm()
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

onMounted(async () => {
  if (canWrite.value) {
    try {
      const teachersResponse = await fetchTeachers({ per_page: 100, status: 'active' })
      teachers.value = teachersResponse.data || []
    } catch { teachers.value = [] }
    try { subjects.value = await fetchSubjects() } catch { subjects.value = [] }
    try { levels.value = await fetchLevels() } catch { levels.value = [] }
  }
  await load()
})
</script>

<template>
  <div class="space-y-5">
    <div>
      <h2 class="text-lg font-semibold text-[var(--rdp-forest)]">{{ t('academicTimetable.title') }}</h2>
      <p class="mt-1 text-sm text-slate-600">{{ t('academicTimetable.subtitle') }}</p>
      <p v-if="yearName" class="mt-1 text-xs text-slate-500">{{ t('academicAttendance.year') }} · {{ yearName }}</p>
    </div>
    <p v-if="!canView" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ t('academicAttendance.forbidden') }}</p>
    <p v-else-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
    <div v-else class="grid gap-6 lg:grid-cols-[1.3fr_0.7fr]">
      <div class="overflow-x-auto rounded-xl border bg-white">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-start text-xs text-slate-500">
            <tr>
              <th class="px-4 py-3 font-medium">{{ t('academicTimetable.sessionDate') }}</th>
              <th class="px-4 py-3 font-medium">{{ t('academicTimetable.sessionTime') }}</th>
              <th class="px-4 py-3 font-medium">{{ t('academicTimetable.teacher') }}</th>
              <th class="px-4 py-3 font-medium">{{ t('academicTimetable.subject') }}</th>
              <th class="px-4 py-3 font-medium">{{ t('academicStudents.level') }}</th>
              <th class="px-4 py-3 font-medium">{{ t('academicAttendance.room') }}</th>
              <th v-if="canWrite" class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td :colspan="canWrite ? 7 : 6" class="px-4 py-8 text-center text-slate-500">{{ t('academicAttendance.loading') }}</td>
            </tr>
            <tr v-else-if="!items.length">
              <td :colspan="canWrite ? 7 : 6" class="px-4 py-8 text-center text-slate-500">{{ t('academicTimetable.empty') }}</td>
            </tr>
            <tr v-for="item in items" :key="item.id" class="border-t">
              <td class="px-4 py-3">
                <p class="font-medium">{{ formatDate(item.session_date) }}</p>
                <p class="text-xs text-slate-500">{{ item.session_date }}</p>
              </td>
              <td class="px-4 py-3 font-medium">{{ item.starts_at }} – {{ item.ends_at }}</td>
              <td class="px-4 py-3">{{ item.teacher?.full_name || '—' }}</td>
              <td class="px-4 py-3">{{ label(item.subject) || '—' }}</td>
              <td class="px-4 py-3">{{ label(item.level) || '—' }}</td>
              <td class="px-4 py-3">{{ item.room || '—' }}</td>
              <td v-if="canWrite" class="px-4 py-3 flex gap-2">
                <button type="button" class="text-teal-800 hover:underline" @click="edit(item)">{{ t('forms.edit') }}</button>
                <button type="button" class="text-rose-700 hover:underline" @click="remove(item)">{{ t('forms.delete') }}</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <form v-if="canWrite" class="space-y-3 rounded-xl border bg-white p-5" @submit.prevent="save">
        <h3 class="font-semibold">{{ editingId ? t('academicTimetable.editSession') : t('academicTimetable.newSession') }}</h3>
        <p class="text-xs text-slate-500">{{ t('academicTimetable.formHint') }}</p>
        <select v-model="form.teacher_id" required class="w-full rounded border px-3 py-2 text-sm">
          <option value="">{{ t('academicTimetable.teacher') }}</option>
          <option v-for="teacher in teachers" :key="teacher.id" :value="teacher.id">{{ teacher.full_name }}</option>
        </select>
        <select v-model="form.subject_id" required class="w-full rounded border px-3 py-2 text-sm">
          <option value="">{{ t('academicTimetable.subject') }}</option>
          <option v-for="subject in offeredSubjects" :key="subject.id" :value="subject.id">{{ label(subject) }}</option>
        </select>
        <select v-model="form.level_id" required class="w-full rounded border px-3 py-2 text-sm">
          <option value="">{{ t('academicStudents.level') }}</option>
          <option v-for="level in levels" :key="level.id" :value="level.id">{{ label(level) }}</option>
        </select>
        <input v-model="form.session_date" type="date" required class="w-full rounded border px-3 py-2 text-sm" />
        <div class="grid grid-cols-2 gap-2">
          <input v-model="form.starts_at" type="time" required class="w-full rounded border px-3 py-2 text-sm" />
          <input v-model="form.ends_at" type="time" required class="w-full rounded border px-3 py-2 text-sm" />
        </div>
        <input v-model="form.room" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('academicAttendance.room')" />
        <div class="flex gap-2">
          <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white" :disabled="saving">{{ t('forms.save') }}</button>
          <button v-if="editingId" type="button" class="rounded border px-4 py-2 text-sm" @click="resetForm">{{ t('forms.cancel') }}</button>
        </div>
      </form>
    </div>
  </div>
</template>
