<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { fetchClassStaff, updateClassStaff, upsertClassSupervisorVisit, deleteClassSupervisorVisit } from '@/services/academic'
import { pickName } from '@/utils/localized'

const { t, locale } = useI18n()
const route = useRoute()
const auth = useAuthStore()

const isSupervisorPage = computed(() => route.meta.classStaffRole === 'supervisor')
const pageTitle = computed(() => (
  isSupervisorPage.value ? t('academicClassStaff.supervisor') : t('academicClassStaff.counselor')
))
const pageSubtitle = computed(() => (
  isSupervisorPage.value ? t('academicClassStaff.supervisorHint') : t('academicClassStaff.counselorHint')
))

const loading = ref(false)
const savingKey = ref('')
const error = ref('')
const success = ref('')
const academicYear = ref(null)
const months = ref([])
const levels = ref([])

const canView = computed(() => auth.hasPermission('teacher.view'))
const canUpdate = computed(() => auth.hasPermission('teacher.update') || auth.hasPermission('teacher.create'))

function label(item) {
  return pickName(item, locale.value)
}

function normalizeLevel(level) {
  const visits = { ...(level.visits || {}) }
  const visitDates = {}
  months.value.forEach((month) => {
    visitDates[month] = visits[month]?.visited_on || `${month}-01`
  })
  return {
    ...level,
    supervisor_name: level.supervisor_name || level.supervisor?.full_name || '',
    counselor_name: level.counselor_name || level.counselor?.full_name || '',
    visits,
    visitDates,
  }
}

function monthLabel(month) {
  const [year, monthNumber] = String(month).split('-').map(Number)
  if (!year || !monthNumber) return month
  return new Intl.DateTimeFormat(locale.value === 'ar' ? 'ar' : locale.value, {
    month: 'long',
    year: 'numeric',
  }).format(new Date(year, monthNumber - 1, 1))
}

function monthEnd(month) {
  const [year, monthNumber] = String(month).split('-').map(Number)
  if (!year || !monthNumber) return `${month}-28`
  const last = new Date(year, monthNumber, 0).getDate()
  return `${year}-${String(monthNumber).padStart(2, '0')}-${String(last).padStart(2, '0')}`
}

function hasVisit(level, month) {
  return Boolean(level.visits?.[month]?.visited_on)
}

async function load() {
  if (!canView.value) return
  loading.value = true
  error.value = ''
  try {
    const data = await fetchClassStaff()
    academicYear.value = data.academic_year || null
    months.value = data.months || []
    levels.value = (data.levels || []).map(normalizeLevel)
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

function payloadFor(level) {
  return {
    supervisor_name: (level.supervisor_name || '').trim() || null,
    counselor_name: (level.counselor_name || '').trim() || null,
  }
}

async function savePeople(level, role) {
  if (!canUpdate.value) return
  savingKey.value = `${role}-${level.id}`
  error.value = ''
  success.value = ''
  try {
    const updated = await updateClassStaff(level.id, payloadFor(level))
    levels.value = levels.value.map((item) => (
      item.id === updated.id ? { ...item, ...normalizeLevel({ ...item, ...updated }) } : item
    ))
    success.value = t('academicClassStaff.saved', { level: label(level) })
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || e.message
  } finally {
    savingKey.value = ''
  }
}

async function saveVisit(level, month) {
  if (!canUpdate.value) return
  savingKey.value = `visit-${level.id}-${month}`
  error.value = ''
  success.value = ''
  try {
    const visit = await upsertClassSupervisorVisit(level.id, {
      month,
      visited_on: level.visitDates?.[month] || `${month}-01`,
    })
    levels.value = levels.value.map((item) => {
      if (item.id !== level.id) return item
      return {
        ...item,
        visits: { ...item.visits, [month]: visit },
        visitDates: { ...item.visitDates, [month]: visit.visited_on || item.visitDates[month] },
      }
    })
    success.value = t('academicClassStaff.visitSaved', { level: label(level), month: monthLabel(month) })
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || e.message
  } finally {
    savingKey.value = ''
  }
}

async function clearVisit(level, month) {
  if (!canUpdate.value) return
  savingKey.value = `visit-${level.id}-${month}`
  error.value = ''
  success.value = ''
  try {
    await deleteClassSupervisorVisit(level.id, month)
    levels.value = levels.value.map((item) => {
      if (item.id !== level.id) return item
      const visits = { ...item.visits }
      delete visits[month]
      return { ...item, visits }
    })
    success.value = t('academicClassStaff.visitCleared', { level: label(level), month: monthLabel(month) })
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    savingKey.value = ''
  }
}

onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div>
      <h2 class="text-lg font-semibold text-[var(--rdp-forest)]">{{ pageTitle }}</h2>
      <p class="mt-1 text-sm text-slate-600">{{ pageSubtitle }}</p>
      <p v-if="academicYear?.name" class="mt-1 text-xs text-slate-500">{{ t('academicClassStaff.year') }}: {{ academicYear.name }}</p>
    </div>
    <p v-if="!canView" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ t('academicClassStaff.forbidden') }}</p>
    <template v-else>
      <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
      <p v-if="success" class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ success }}</p>
      <p v-if="loading" class="text-sm text-slate-500">{{ t('academicClassStaff.loading') }}</p>
      <p v-else-if="!levels.length" class="rounded-xl border bg-white px-4 py-8 text-center text-sm text-slate-500">{{ t('academicClassStaff.empty') }}</p>
      <template v-else>
        <section v-if="!isSupervisorPage" class="space-y-3">
          <div class="overflow-x-auto rounded-xl border bg-white">
            <table class="min-w-full text-sm">
              <thead class="bg-slate-50 text-start text-xs text-slate-500">
                <tr>
                  <th class="px-4 py-3 font-medium">{{ t('academicClassStaff.level') }}</th>
                  <th class="px-4 py-3 font-medium">{{ t('academicClassStaff.counselor') }}</th>
                  <th v-if="canUpdate" class="px-4 py-3"></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="level in levels" :key="`counselor-${level.id}`" class="border-t">
                  <td class="px-4 py-3">
                    <p class="font-medium">{{ label(level) }}</p>
                    <p v-if="level.stage" class="text-xs text-slate-500">{{ label(level.stage) }}</p>
                  </td>
                  <td class="px-4 py-3">
                    <input
                      v-model="level.counselor_name"
                      type="text"
                      class="w-full min-w-[12rem] rounded border px-3 py-2 text-sm"
                      :placeholder="t('academicClassStaff.namePlaceholder')"
                      :disabled="!canUpdate"
                    />
                  </td>
                  <td v-if="canUpdate" class="px-4 py-3">
                    <button
                      type="button"
                      class="rounded bg-teal-800 px-3 py-2 text-sm text-white disabled:opacity-60"
                      :disabled="savingKey === `counselor-${level.id}`"
                      @click="savePeople(level, 'counselor')"
                    >
                      {{ savingKey === `counselor-${level.id}` ? t('academicClassStaff.saving') : t('forms.save') }}
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </section>

        <section v-else class="space-y-3">
          <div v-for="level in levels" :key="`supervisor-${level.id}`" class="space-y-3 rounded-xl border bg-white p-4">
            <div class="flex flex-wrap items-end gap-3">
              <div class="min-w-[12rem] flex-1">
                <p class="font-medium">{{ label(level) }}</p>
                <p v-if="level.stage" class="text-xs text-slate-500">{{ label(level.stage) }}</p>
              </div>
              <label class="block min-w-[14rem] flex-1 text-xs text-slate-500">
                {{ t('academicClassStaff.supervisor') }}
                <input
                  v-model="level.supervisor_name"
                  type="text"
                  class="mt-1 w-full rounded border px-3 py-2 text-sm text-slate-800"
                  :placeholder="t('academicClassStaff.namePlaceholder')"
                  :disabled="!canUpdate"
                />
              </label>
              <button
                v-if="canUpdate"
                type="button"
                class="rounded bg-teal-800 px-3 py-2 text-sm text-white disabled:opacity-60"
                :disabled="savingKey === `supervisor-${level.id}`"
                @click="savePeople(level, 'supervisor')"
              >
                {{ savingKey === `supervisor-${level.id}` ? t('academicClassStaff.saving') : t('forms.save') }}
              </button>
            </div>
            <p class="text-xs font-medium text-slate-500">{{ t('academicClassStaff.monthlyVisits') }}</p>
            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
              <div
                v-for="month in months"
                :key="`${level.id}-${month}`"
                class="rounded-lg border px-3 py-2"
                :class="hasVisit(level, month) ? 'border-emerald-200 bg-emerald-50' : 'border-slate-200'"
              >
                <p class="text-sm font-medium">{{ monthLabel(month) }}</p>
                <p class="text-xs" :class="hasVisit(level, month) ? 'text-emerald-800' : 'text-slate-500'">
                  {{ hasVisit(level, month) ? t('academicClassStaff.visited') : t('academicClassStaff.notVisited') }}
                </p>
                <input
                  v-model="level.visitDates[month]"
                  type="date"
                  class="mt-2 w-full rounded border px-2 py-1 text-sm"
                  :min="`${month}-01`"
                  :max="monthEnd(month)"
                  :disabled="!canUpdate"
                />
                <div v-if="canUpdate" class="mt-2 flex flex-wrap gap-2">
                  <button
                    type="button"
                    class="rounded border border-teal-800 px-2 py-1 text-xs text-teal-800 disabled:opacity-60"
                    :disabled="savingKey === `visit-${level.id}-${month}`"
                    @click="saveVisit(level, month)"
                  >
                    {{ t('academicClassStaff.markVisit') }}
                  </button>
                  <button
                    v-if="hasVisit(level, month)"
                    type="button"
                    class="rounded border px-2 py-1 text-xs text-rose-700 disabled:opacity-60"
                    :disabled="savingKey === `visit-${level.id}-${month}`"
                    @click="clearVisit(level, month)"
                  >
                    {{ t('academicClassStaff.clearVisit') }}
                  </button>
                </div>
              </div>
            </div>
          </div>
        </section>
      </template>
    </template>
  </div>
</template>
