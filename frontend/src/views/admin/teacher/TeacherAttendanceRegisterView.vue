<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  createStudent,
  deleteStudent,
  fetchTeacherRegister,
  updateStudent,
  upsertTeacherRegister,
} from '@/services/academic'
import { pickName } from '@/utils/localized'

const { t, locale } = useI18n()
const auth = useAuthStore()

const loading = ref(false)
const error = ref('')
const students = ref([])
const levels = ref([])
const selectedLevelId = ref('')
const editingId = ref(null)
const savingId = ref(null)

const canManage = computed(() => (
  auth.hasPermission('attendance.create')
  || auth.hasPermission('attendance.update')
  || auth.hasPermission('attendance.delete')
  || auth.user?.roles?.some((r) => ['TEACHER', 'SUPER_ADMIN'].includes(r.code))
))

const form = reactive({
  first_name: '',
  last_name: '',
  level_id: '',
})

function isoDate(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

function startOfSaturdayWeek(date) {
  const d = new Date(date)
  d.setHours(0, 0, 0, 0)
  const offset = (d.getDay() + 1) % 7
  d.setDate(d.getDate() - offset)
  return d
}

const weekStart = ref(startOfSaturdayWeek(new Date()))

const days = computed(() => {
  return Array.from({ length: 7 }, (_, index) => {
    const date = new Date(weekStart.value)
    date.setDate(weekStart.value.getDate() + index)
    return {
      iso: isoDate(date),
      weekday: date.getDay(),
      label: date.toLocaleDateString(
        { ar: 'ar-SD', fr: 'fr-FR', en: 'en-GB' }[locale.value] || 'fr-FR',
        { weekday: 'short', day: 'numeric', month: 'numeric' },
      ),
      today: isoDate(date) === isoDate(new Date()),
    }
  })
})

const weekLabel = computed(() => {
  const first = days.value[0]
  const last = days.value[6]
  return `${first.label} — ${last.label}`
})

const visibleStudents = computed(() => students.value)

function levelName(item) {
  return pickName(item?.level || item, locale.value) || '—'
}

function statusClass(status) {
  return {
    present: 'bg-emerald-600 text-white',
    absent: 'bg-rose-600 text-white',
    late: 'bg-amber-500 text-white',
    excused: 'bg-sky-600 text-white',
  }[status] || 'border bg-white text-slate-600'
}

function dayStatus(student, iso) {
  return student.days?.[iso]?.status || ''
}

function shiftWeek(delta) {
  const next = new Date(weekStart.value)
  next.setDate(next.getDate() + (delta * 7))
  weekStart.value = next
}

function resetForm() {
  editingId.value = null
  form.first_name = ''
  form.last_name = ''
  form.level_id = selectedLevelId.value
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await fetchTeacherRegister({
      from: days.value[0].iso,
      to: days.value[6].iso,
      level_id: selectedLevelId.value || undefined,
    })
    students.value = data.students || []
    levels.value = data.levels || []
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

async function mark(student, iso, status) {
  if (!canManage.value) return
  savingId.value = `${student.id}-${iso}`
  const next = dayStatus(student, iso) === status ? '' : status
  try {
    await upsertTeacherRegister(student.id, {
      attendance_date: iso,
      status: next || null,
    })
    if (!student.days) student.days = {}
    if (next) student.days[iso] = { status: next, notes: student.days[iso]?.notes || null }
    else delete student.days[iso]
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    savingId.value = null
  }
}

function startEdit(student) {
  editingId.value = student.id
  form.first_name = student.first_name
  form.last_name = student.last_name
  form.level_id = student.level_id || ''
}

async function saveStudent() {
  if (!canManage.value) return
  error.value = ''
  try {
    const payload = {
      first_name: form.first_name,
      last_name: form.last_name,
      level_id: form.level_id || null,
      status: 'active',
    }
    if (editingId.value) await updateStudent(editingId.value, payload)
    else await createStudent(payload)
    resetForm()
    await load()
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : (e.response?.data?.message || e.message)
  }
}

async function removeStudent(student) {
  if (!confirm(t('teacherRegister.confirmDelete', { name: student.full_name }))) return
  try {
    await deleteStudent(student.id)
    if (editingId.value === student.id) resetForm()
    await load()
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

function countFor(iso, status) {
  return visibleStudents.value.filter((student) => dayStatus(student, iso) === status).length
}

watch([weekStart, selectedLevelId], load)
onMounted(load)
</script>

<template>
  <div class="space-y-4">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold text-[var(--rdp-forest)]">{{ t('teacherRegister.title') }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ t('teacherRegister.subtitle') }}</p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <button type="button" class="rounded border px-3 py-1.5 text-sm" @click="shiftWeek(-1)">←</button>
        <p class="min-w-[10rem] text-center text-sm font-medium">{{ weekLabel }}</p>
        <button type="button" class="rounded border px-3 py-1.5 text-sm" @click="shiftWeek(1)">→</button>
        <button type="button" class="rounded bg-teal-800 px-3 py-1.5 text-sm text-white" @click="weekStart = startOfSaturdayWeek(new Date())">
          {{ t('teacherRegister.thisWeek') }}
        </button>
      </div>
    </div>

    <div class="flex flex-wrap gap-2">
      <button
        type="button"
        class="rounded-full px-3 py-1.5 text-sm"
        :class="!selectedLevelId ? 'bg-teal-800 text-white' : 'border bg-white'"
        @click="selectedLevelId = ''"
      >
        {{ t('teacherRegister.allLevels') }}
      </button>
      <button
        v-for="level in levels"
        :key="level.id"
        type="button"
        class="rounded-full px-3 py-1.5 text-sm"
        :class="String(selectedLevelId) === String(level.id) ? 'bg-teal-800 text-white' : 'border bg-white'"
        @click="selectedLevelId = String(level.id)"
      >
        {{ levelName(level) }}
      </button>
    </div>

    <form
      v-if="canManage"
      class="grid gap-2 rounded-xl border bg-white p-3 sm:grid-cols-2 lg:grid-cols-5"
      @submit.prevent="saveStudent"
    >
      <input v-model="form.first_name" required class="rounded border px-3 py-2 text-sm" :placeholder="t('teacherRegister.firstName')" />
      <input v-model="form.last_name" required class="rounded border px-3 py-2 text-sm" :placeholder="t('teacherRegister.lastName')" />
      <select v-model="form.level_id" class="rounded border px-3 py-2 text-sm">
        <option value="">{{ t('teacherRegister.level') }}</option>
        <option v-for="level in levels" :key="level.id" :value="level.id">{{ levelName(level) }}</option>
      </select>
      <button type="submit" class="rounded bg-teal-800 px-3 py-2 text-sm text-white">
        {{ editingId ? t('teacherRegister.saveStudent') : t('teacherRegister.addStudent') }}
      </button>
      <button v-if="editingId" type="button" class="rounded border px-3 py-2 text-sm" @click="resetForm">
        {{ t('forms.cancel') }}
      </button>
    </form>

    <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
    <p v-else-if="loading" class="text-sm text-slate-500">{{ t('teacherRegister.loading') }}</p>

    <div class="overflow-x-auto rounded-xl border bg-white">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-xs text-slate-500">
          <tr>
            <th class="sticky start-0 bg-slate-50 px-3 py-3 text-start font-medium">#</th>
            <th class="sticky start-8 bg-slate-50 px-3 py-3 text-start font-medium">{{ t('teacherRegister.student') }}</th>
            <th class="px-3 py-3 text-start font-medium">{{ t('teacherRegister.level') }}</th>
            <th v-for="day in days" :key="day.iso" class="px-2 py-3 text-center font-medium" :class="day.today ? 'text-teal-800' : ''">
              {{ day.label }}
            </th>
            <th v-if="canManage" class="px-3 py-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(student, index) in visibleStudents" :key="student.id" class="border-t">
            <td class="px-3 py-2 text-slate-400">{{ index + 1 }}</td>
            <td class="px-3 py-2 font-medium">{{ student.full_name }}</td>
            <td class="px-3 py-2 text-slate-600">{{ levelName(student) }}</td>
            <td v-for="day in days" :key="`${student.id}-${day.iso}`" class="px-1 py-2">
              <div class="flex flex-wrap justify-center gap-0.5">
                <button
                  v-for="st in ['present', 'absent']"
                  :key="st"
                  type="button"
                  class="rounded px-1.5 py-1 text-[11px] font-semibold"
                  :class="statusClass(dayStatus(student, day.iso) === st ? st : '')"
                  :disabled="!canManage || savingId === `${student.id}-${day.iso}`"
                  @click="mark(student, day.iso, st)"
                >
                  {{ t(`teacherRegister.short.${st}`) }}
                </button>
              </div>
            </td>
            <td v-if="canManage" class="px-3 py-2 text-end">
              <div class="flex flex-wrap justify-end gap-3">
                <button type="button" class="text-xs font-semibold text-teal-800 hover:underline" @click="startEdit(student)">
                  {{ t('forms.edit') }}
                </button>
                <button type="button" class="text-xs font-semibold text-rose-700 hover:underline" @click="removeStudent(student)">
                  {{ t('forms.delete') }}
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!visibleStudents.length && !loading" class="border-t">
            <td :colspan="canManage ? 11 : 10" class="px-4 py-8 text-center text-slate-500">
              {{ t('teacherRegister.empty') }}
            </td>
          </tr>
        </tbody>
        <tfoot v-if="visibleStudents.length" class="border-t bg-slate-50 text-xs">
          <tr>
            <td colspan="3" class="px-3 py-2 font-medium">{{ t('teacherRegister.totals') }}</td>
            <td v-for="day in days" :key="`tot-${day.iso}`" class="px-2 py-2 text-center">
              <span class="text-emerald-700">{{ countFor(day.iso, 'present') }}</span>
              /
              <span class="text-rose-700">{{ countFor(day.iso, 'absent') }}</span>
            </td>
            <td v-if="canManage"></td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</template>
