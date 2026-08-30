<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  createClassSession,
  deleteClassSession,
  fetchClassSessions,
  updateClassSession,
} from '@/services/academic'

const props = defineProps({
  classId: { type: [Number, String], default: null },
  attendanceBase: { type: String, required: true },
})

const emit = defineEmits(['error'])
const { t } = useI18n()
const router = useRouter()
const auth = useAuthStore()
const sessions = ref([])
const editingId = ref(null)

const canManage = computed(() => (
  auth.hasPermission('attendance.create')
  || auth.hasPermission('attendance.update')
  || auth.hasPermission('attendance.delete')
))

const form = reactive({
  session_date: new Date().toISOString().slice(0, 10),
  starts_at: '10:00',
  ends_at: '11:00',
  room: '',
})

function timeValue(value) {
  return String(value || '').slice(0, 5)
}

function payloadFrom(source) {
  return {
    session_date: (source.session_date || '').slice(0, 10),
    starts_at: timeValue(source.starts_at),
    ends_at: timeValue(source.ends_at),
    room: source.room || '',
  }
}

async function loadSessions() {
  if (!props.classId) {
    sessions.value = []
    return
  }
  try {
    const data = await fetchClassSessions(props.classId)
    sessions.value = data.data || []
  } catch (e) {
    emit('error', e.response?.data?.message || e.message)
  }
}

async function saveSession() {
  try {
    if (editingId.value) {
      await updateClassSession(editingId.value, { ...form })
      editingId.value = null
      await loadSessions()
      return
    }
    const session = await createClassSession(props.classId, { ...form })
    await loadSessions()
    router.push(`${props.attendanceBase}/sessions/${session.id}`)
  } catch (e) {
    emit('error', e.response?.data?.message || e.message)
  }
}

function startEdit(session) {
  editingId.value = session.id
  Object.assign(form, payloadFrom(session))
}

function cancelEdit() {
  editingId.value = null
  Object.assign(form, {
    session_date: new Date().toISOString().slice(0, 10),
    starts_at: '10:00',
    ends_at: '11:00',
    room: '',
  })
}

async function removeSession(session) {
  if (!confirm(t('academicAttendance.confirmDeleteSession'))) return
  try {
    await deleteClassSession(session.id)
    if (editingId.value === session.id) cancelEdit()
    await loadSessions()
  } catch (e) {
    emit('error', e.response?.data?.message || e.message)
  }
}

watch(() => props.classId, () => {
  cancelEdit()
  loadSessions()
}, { immediate: true })

defineExpose({ reload: loadSessions })
</script>

<template>
  <div class="space-y-4">
    <form
      v-if="canManage && classId"
      class="grid gap-3 rounded-xl border bg-white p-4 sm:grid-cols-2 lg:grid-cols-6"
      @submit.prevent="saveSession"
    >
      <input v-model="form.session_date" type="date" required class="rounded border px-3 py-2 text-sm" />
      <input v-model="form.starts_at" type="time" required class="rounded border px-3 py-2 text-sm" />
      <input v-model="form.ends_at" type="time" required class="rounded border px-3 py-2 text-sm" />
      <input v-model="form.room" :placeholder="t('academicAttendance.room')" class="rounded border px-3 py-2 text-sm" />
      <button type="submit" class="rounded bg-teal-800 px-3 py-2 text-sm text-white">
        {{ editingId ? t('academicAttendance.saveSession') : t('academicAttendance.newSession') }}
      </button>
      <button
        v-if="editingId"
        type="button"
        class="rounded border px-3 py-2 text-sm"
        @click="cancelEdit"
      >
        {{ t('forms.cancel') }}
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
            <td class="px-4 py-3">{{ timeValue(s.starts_at) }} – {{ timeValue(s.ends_at) }}</td>
            <td class="px-4 py-3 text-emerald-700">{{ s.present_count }}</td>
            <td class="px-4 py-3 text-rose-700">{{ s.absent_count }}</td>
            <td class="px-4 py-3">
              <div class="flex flex-wrap justify-end gap-3">
                <RouterLink
                  :to="`${attendanceBase}/sessions/${s.id}`"
                  class="text-xs font-semibold text-[var(--rdp-forest)] hover:underline"
                >
                  {{ t('academicAttendance.sheet') }}
                </RouterLink>
                <button
                  v-if="canManage"
                  type="button"
                  class="text-xs font-semibold text-teal-800 hover:underline"
                  @click="startEdit(s)"
                >
                  {{ t('forms.edit') }}
                </button>
                <button
                  v-if="canManage"
                  type="button"
                  class="text-xs font-semibold text-rose-700 hover:underline"
                  @click="removeSession(s)"
                >
                  {{ t('forms.delete') }}
                </button>
              </div>
            </td>
          </tr>
          <tr v-if="!sessions.length">
            <td colspan="5" class="px-4 py-8 text-center text-slate-500">{{ t('academicAttendance.noSessions') }}</td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
