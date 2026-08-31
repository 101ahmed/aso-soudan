<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  addStudentToClass,
  deleteStudentAttendance,
  fetchClassRoster,
  removeStudentFromClass,
  upsertStudentAttendance,
} from '@/services/academic'

const props = defineProps({
  classId: { type: [Number, String], default: null },
  sessionId: { type: [Number, String], default: null },
  canManage: { type: Boolean, default: false },
})

const emit = defineEmits(['error', 'changed'])
const { t } = useI18n()

const rows = ref([])
const available = ref([])
const addStudentId = ref('')
const busyId = ref(null)

const counts = computed(() => {
  const c = { present: 0, absent: 0, late: 0, excused: 0 }
  for (const row of rows.value) {
    if (c[row.status] != null) c[row.status] += 1
  }
  return c
})

function applyPayload(data) {
  rows.value = (data?.students || data?.rows || []).map((row) => ({
    ...row,
    status: row.status || '',
    notes: row.notes || '',
  }))
  available.value = data?.available_students || []
}

async function loadRoster() {
  if (!props.classId) {
    rows.value = []
    available.value = []
    return
  }
  try {
    const data = await fetchClassRoster(props.classId, props.sessionId)
    applyPayload(data)
  } catch (e) {
    emit('error', e.response?.data?.message || e.message)
  }
}

async function mark(row, status) {
  if (!props.sessionId || !props.canManage) return
  busyId.value = row.student_id
  try {
    const data = await upsertStudentAttendance(props.sessionId, row.student_id, {
      status,
      notes: row.notes || null,
    })
    applyPayload(data)
    emit('changed')
  } catch (e) {
    emit('error', e.response?.data?.message || e.message)
  } finally {
    busyId.value = null
  }
}

async function clearAttendance(row) {
  if (!props.sessionId || !props.canManage) return
  busyId.value = row.student_id
  try {
    const data = await deleteStudentAttendance(props.sessionId, row.student_id)
    applyPayload(data)
    emit('changed')
  } catch (e) {
    emit('error', e.response?.data?.message || e.message)
  } finally {
    busyId.value = null
  }
}

async function addStudent() {
  if (!addStudentId.value || !props.classId) return
  try {
    const data = await addStudentToClass(props.classId, addStudentId.value, props.sessionId)
    applyPayload(data)
    addStudentId.value = ''
    emit('changed')
  } catch (e) {
    emit('error', e.response?.data?.message || e.message)
  }
}

async function removeStudent(row) {
  if (!confirm(t('academicAttendance.confirmRemoveStudent', { name: row.full_name }))) return
  try {
    const data = await removeStudentFromClass(props.classId, row.student_id, props.sessionId)
    applyPayload(data)
    emit('changed')
  } catch (e) {
    emit('error', e.response?.data?.message || e.message)
  }
}

function statusClass(status) {
  return {
    present: 'bg-emerald-600 text-white',
    absent: 'bg-rose-600 text-white',
    late: 'bg-amber-500 text-white',
    excused: 'bg-sky-600 text-white',
  }[status] || 'border bg-white text-slate-700'
}

watch(() => [props.classId, props.sessionId], loadRoster, { immediate: true })

defineExpose({ reload: loadRoster })
</script>

<template>
  <section class="space-y-3">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <h3 class="text-sm font-semibold text-[var(--rdp-forest)]">
        {{ t('academicAttendance.rosterTitle') }}
        <span class="font-normal text-slate-500">({{ rows.length }})</span>
      </h3>
      <div v-if="sessionId" class="flex flex-wrap gap-2 text-xs">
        <span class="text-emerald-700">{{ counts.present }} {{ t('academicAttendance.present') }}</span>
        <span class="text-rose-700">{{ counts.absent }} {{ t('academicAttendance.absent') }}</span>
        <span class="text-amber-700">{{ counts.late }} {{ t('academicAttendance.late') }}</span>
        <span class="text-sky-700">{{ counts.excused }} {{ t('academicAttendance.excused') }}</span>
      </div>
    </div>

    <p v-if="canManage && !sessionId" class="rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
      {{ t('academicAttendance.pickSession') }}
    </p>

    <form v-if="canManage" class="flex flex-wrap gap-2" @submit.prevent="addStudent">
      <select v-model="addStudentId" class="min-w-[12rem] flex-1 rounded border px-3 py-2 text-sm">
        <option value="">{{ t('academicAttendance.chooseStudent') }}</option>
        <option v-for="student in available" :key="student.id" :value="student.id">
          {{ student.full_name }}
        </option>
      </select>
      <button type="submit" class="rounded bg-teal-800 px-3 py-2 text-sm text-white" :disabled="!addStudentId">
        {{ t('academicAttendance.addStudent') }}
      </button>
    </form>

    <div class="overflow-hidden rounded-xl border bg-white">
      <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-xs text-slate-500">
          <tr>
            <th class="px-4 py-3 text-start font-medium">#</th>
            <th class="px-4 py-3 text-start font-medium">{{ t('academicAttendance.student') }}</th>
            <th class="px-4 py-3 text-start font-medium">{{ t('academicAttendance.status') }}</th>
            <th v-if="canManage" class="px-4 py-3"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(row, index) in rows" :key="row.student_id" class="border-t">
            <td class="px-4 py-3 text-slate-400">{{ index + 1 }}</td>
            <td class="px-4 py-3 font-medium">{{ row.full_name }}</td>
            <td class="px-4 py-3">
              <div class="flex flex-wrap gap-1">
                <button
                  v-for="st in ['present', 'absent', 'late', 'excused']"
                  :key="st"
                  type="button"
                  class="rounded px-2 py-1 text-xs font-semibold"
                  :class="statusClass(row.status === st ? st : '')"
                  :disabled="!canManage || !sessionId || busyId === row.student_id"
                  @click="mark(row, st)"
                >
                  {{ t(`academicAttendance.statuses.${st}`) }}
                </button>
              </div>
            </td>
            <td v-if="canManage" class="px-4 py-3 text-end">
              <div class="flex flex-wrap justify-end gap-3">
                <button
                  v-if="sessionId && row.recorded"
                  type="button"
                  class="text-xs font-semibold text-slate-600 hover:underline"
                  @click="clearAttendance(row)"
                >
                  {{ t('academicAttendance.clearStatus') }}
                </button>
                <button
                  type="button"
                  class="text-xs font-semibold text-rose-700 hover:underline"
                  @click="removeStudent(row)"
                >
                  {{ t('academicAttendance.removeFromClass') }}
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!rows.length">
            <td :colspan="canManage ? 4 : 3" class="px-4 py-8 text-center text-slate-500">
              {{ t('academicAttendance.noStudents') }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </section>
</template>
