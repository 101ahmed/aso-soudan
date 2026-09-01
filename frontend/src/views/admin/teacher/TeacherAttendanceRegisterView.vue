<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  createStudent,
  deleteStudent,
  downloadTeacherRegisterPdf,
  fetchTeacherRegister,
  renameTeacherRegisterStudent,
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
const downloading = ref(false)

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
const nameForm = reactive({
  first_name: '',
  last_name: '',
})

function isoDate(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

function sundayOfWeek(date) {
  const d = new Date(date)
  d.setHours(0, 0, 0, 0)
  d.setDate(d.getDate() - d.getDay())
  return d
}

const selectedSunday = ref(sundayOfWeek(new Date()))

const days = computed(() => {
  const date = new Date(selectedSunday.value)
  return [{
    iso: isoDate(date),
    weekday: 0,
    label: date.toLocaleDateString(
      { ar: 'ar-SD', fr: 'fr-FR', en: 'en-GB' }[locale.value] || 'fr-FR',
      { weekday: 'long', day: 'numeric', month: 'numeric' },
    ),
    today: isoDate(date) === isoDate(new Date()),
  }]
})

const weekLabel = computed(() => days.value[0]?.label || '')
const emptyColspan = computed(() => 3 + days.value.length + (canManage.value ? 1 : 0))

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
  const next = new Date(selectedSunday.value)
  next.setDate(selectedSunday.value.getDate() + (delta * 7))
  selectedSunday.value = next
}

function resetForm() {
  editingId.value = null
  form.first_name = ''
  form.last_name = ''
  form.level_id = selectedLevelId.value
  nameForm.first_name = ''
  nameForm.last_name = ''
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await fetchTeacherRegister({
      from: days.value[0].iso,
      to: days.value[days.value.length - 1].iso,
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
  nameForm.first_name = student.first_name
  nameForm.last_name = student.last_name
}

async function saveName(student) {
  if (!canManage.value || !editingId.value) return
  error.value = ''
  savingId.value = `name-${student.id}`
  try {
    const updated = await renameTeacherRegisterStudent(student.id, {
      first_name: nameForm.first_name.trim(),
      last_name: nameForm.last_name.trim(),
    })
    student.first_name = updated.first_name
    student.last_name = updated.last_name
    student.full_name = updated.full_name
    resetForm()
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : (e.response?.data?.message || e.message)
  } finally {
    savingId.value = null
  }
}

async function saveStudent() {
  if (!canManage.value) return
  error.value = ''
  try {
    await createStudent({
      first_name: form.first_name,
      last_name: form.last_name,
      level_id: form.level_id || null,
      status: 'active',
    })
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

async function downloadPdf() {
  downloading.value = true
  error.value = ''
  try {
    const { blob, contentType } = await downloadTeacherRegisterPdf({
      from: days.value[0].iso,
      to: days.value[days.value.length - 1].iso,
      level_id: selectedLevelId.value || undefined,
      locale: locale.value,
    })
    if (blob.type.includes('json') || String(contentType).includes('json')) {
      const payload = JSON.parse(await blob.text())
      throw new Error(payload.message || t('teacherRegister.downloadFailed'))
    }
    const url = URL.createObjectURL(blob)
    const isPdf = String(contentType).includes('pdf') || blob.type.includes('pdf')
    const a = document.createElement('a')
    a.href = url
    a.download = `attendance-${days.value[0].iso}.${isPdf ? 'pdf' : 'html'}`
    document.body.appendChild(a)
    a.click()
    a.remove()
    setTimeout(() => URL.revokeObjectURL(url), 1500)
  } catch (e) {
    const data = e.response?.data
    if (data instanceof Blob) {
      try {
        const payload = JSON.parse(await data.text())
        error.value = payload.message || t('teacherRegister.downloadFailed')
      } catch {
        error.value = t('teacherRegister.downloadFailed')
      }
    } else {
      error.value = e.message || e.response?.data?.message || t('teacherRegister.downloadFailed')
    }
  } finally {
    downloading.value = false
  }
}

watch([selectedSunday, selectedLevelId], load)
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
        <button type="button" class="rounded bg-teal-800 px-3 py-1.5 text-sm text-white" @click="selectedSunday = sundayOfWeek(new Date())">
          {{ t('teacherRegister.thisWeek') }}
        </button>
        <button
          type="button"
          class="rounded border border-teal-800 px-3 py-1.5 text-sm font-semibold text-teal-800 disabled:opacity-60"
          :disabled="downloading"
          @click="downloadPdf"
        >
          ⬇️ {{ downloading ? t('teacherRegister.downloading') : t('teacherRegister.downloadPdf') }}
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
        {{ t('teacherRegister.addStudent') }}
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
            <td class="px-3 py-2 font-medium">
              <form
                v-if="editingId === student.id"
                class="flex flex-wrap items-center gap-2"
                @submit.prevent="saveName(student)"
              >
                <input v-model="nameForm.first_name" required class="w-28 rounded border px-2 py-1 text-sm" :placeholder="t('teacherRegister.firstName')" />
                <input v-model="nameForm.last_name" required class="w-28 rounded border px-2 py-1 text-sm" :placeholder="t('teacherRegister.lastName')" />
                <button type="submit" class="text-xs font-semibold text-teal-800 hover:underline" :disabled="savingId === `name-${student.id}`">
                  {{ t('teacherRegister.saveName') }}
                </button>
                <button type="button" class="text-xs text-slate-500 hover:underline" @click="resetForm">
                  {{ t('forms.cancel') }}
                </button>
              </form>
              <span v-else>{{ student.full_name }}</span>
            </td>
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
            <td :colspan="emptyColspan" class="px-4 py-8 text-center text-slate-500">
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
