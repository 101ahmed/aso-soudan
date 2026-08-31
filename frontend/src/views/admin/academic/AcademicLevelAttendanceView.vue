<script setup>
import { computed, onActivated, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { fetchClassesByLevel } from '@/services/academic'
import { attendanceBaseFromPath } from '@/utils/academicPaths'
import { pickName } from '@/utils/localized'
import AttendanceSessionsPanel from '@/components/admin/AttendanceSessionsPanel.vue'

const route = useRoute()
const { t, locale } = useI18n()
const attendanceBase = computed(() => attendanceBaseFromPath(route.path))

const levelId = computed(() => route.params.levelId)
const level = ref(null)
const classes = ref([])
const selectedClassId = ref(null)
const error = ref('')

function label(item) {
  if (!item) return ''
  return pickName(item, locale.value)
}

function classLabel(item) {
  return label(item.subject) || item.name
}

async function loadClasses() {
  error.value = ''
  try {
    const data = await fetchClassesByLevel(levelId.value)
    level.value = data.level
    classes.value = data.data || []
    if (!classes.value.some((item) => item.id === selectedClassId.value)) {
      selectedClassId.value = classes.value[0]?.id || null
    }
    const wantedSubject = route.query.subjectId
    if (wantedSubject) {
      const match = classes.value.find((item) => String(item.subject_id || item.subject?.id) === String(wantedSubject))
      if (match) selectedClassId.value = match.id
    }
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
}

watch(levelId, async () => {
  selectedClassId.value = null
  await loadClasses()
})
onMounted(loadClasses)
onActivated(loadClasses)
</script>

<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <RouterLink :to="attendanceBase" class="text-xs text-[var(--rdp-forest)] hover:underline">
          ← {{ t('academicAttendance.back') }}
        </RouterLink>
        <h2 class="mt-1 text-lg font-semibold text-[var(--rdp-forest)]">{{ label(level) }}</h2>
        <p class="text-sm text-slate-600">{{ t('academicAttendance.byLevelHint') }}</p>
      </div>
    </div>

    <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>

    <div class="grid gap-4 lg:grid-cols-[240px_1fr]">
      <aside class="space-y-2 rounded-xl border bg-white p-3">
        <p class="px-1 text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ t('academicAttendance.subjects') }}</p>
        <button
          v-for="c in classes"
          :key="c.id"
          type="button"
          class="w-full rounded-lg px-3 py-2 text-start text-sm"
          :class="selectedClassId === c.id ? 'bg-teal-800 text-white' : 'hover:bg-slate-50'"
          @click="selectedClassId = c.id"
        >
          <span class="font-medium">{{ classLabel(c) }}</span>
          <span class="mt-0.5 block text-xs opacity-80">{{ c.students_count }} {{ t('academicAttendance.students') }}</span>
        </button>
        <p v-if="!classes.length" class="px-1 text-xs text-slate-500">{{ t('academicAttendance.noClasses') }}</p>
      </aside>

      <AttendanceSessionsPanel
        :class-id="selectedClassId"
        :attendance-base="attendanceBase"
        @error="error = $event"
        @changed="loadClasses"
      />
    </div>
  </div>
</template>
