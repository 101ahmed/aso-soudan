<script setup>
import { computed, onMounted, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { fetchStatisticsCharts } from '@/services/members'
import DonutChart from '@/components/charts/DonutChart.vue'
import BarChart from '@/components/charts/BarChart.vue'
import AreaChart from '@/components/charts/AreaChart.vue'

const PALETTE = ['#1f4d3a', '#c4a35a', '#0f766e', '#be123c', '#0369a1', '#b45309', '#7c3aed', '#64748b', '#0e7490', '#a16207']

const { t, locale } = useI18n()
const auth = useAuthStore()
const loading = ref(false)
const error = ref('')
const charts = ref(null)
const donutSource = ref('gender')

const canView = computed(
  () => auth.hasPermission('statistics.view') || auth.hasPermission('member.view'),
)

const kpis = computed(() => {
  const data = charts.value?.kpis || {}
  return [
    { key: 'membersTotal', value: data.members_total || 0 },
    { key: 'membersActive', value: data.members_active || 0 },
    { key: 'students', value: data.students_active || 0 },
    { key: 'teachersVolunteers', value: data.teachers_and_volunteers || 0 },
    { key: 'events', value: data.events_published || 0 },
    { key: 'initiatives', value: data.initiatives || 0 },
  ]
})

function colorize(rows, labelFor) {
  return (rows || []).map((row, index) => ({
    key: row.key,
    value: row.total,
    label: labelFor(row.key),
    color: PALETTE[index % PALETTE.length],
  }))
}

function seriesLabel(source, key) {
  if (key === 'unknown') return t('statisticsCharts.unknown')
  if (key === 'other') return t('statisticsCharts.other')
  const map = {
    gender: 'statisticsMembers.genders',
    type: 'statisticsMembers.types',
    subscription: 'statisticsMembers.dues',
    status: 'statisticsMembers.statuses',
  }
  const prefix = map[source]
  if (prefix) {
    const path = `${prefix}.${key}`
    return t(path)
  }
  return key
}

const donutItems = computed(() => {
  const data = charts.value || {}
  const source = donutSource.value
  const rows = {
    gender: data.by_gender,
    type: data.by_type,
    subscription: data.by_subscription,
    status: data.by_status,
  }[source]
  return colorize(rows, (key) => seriesLabel(source, key))
})

const barItems = computed(() =>
  colorize(charts.value?.by_city, (key) => {
    if (key === 'unknown') return t('statisticsCharts.unknown')
    if (key === 'other') return t('statisticsCharts.other')
    if (key === 'Rennes') return t('statisticsMembers.rennes')
    return key
  }),
)

const monthFormatter = computed(
  () => new Intl.DateTimeFormat(locale.value === 'ar' ? 'ar' : locale.value, { month: 'short' }),
)

const areaItems = computed(() =>
  (charts.value?.monthly || []).map((row) => {
    const [year, month] = String(row.key).split('-')
    const date = new Date(Number(year), Number(month) - 1, 1)
    return {
      key: row.key,
      value: row.total,
      label: monthFormatter.value.format(date),
    }
  }),
)

async function load() {
  loading.value = true
  error.value = ''
  try {
    charts.value = await fetchStatisticsCharts()
  } catch (e) {
    error.value = e.response?.data?.message || e.message
    charts.value = null
  } finally {
    loading.value = false
  }
}

onMounted(load)
</script>

<template>
  <div v-if="!canView" class="rounded-xl border border-rose-200 bg-rose-50 p-6 text-sm text-rose-800">
    {{ t('statisticsCharts.forbidden') }}
  </div>

  <div v-else class="space-y-6">
    <div>
      <h2 class="text-lg font-semibold text-[var(--rdp-forest)]">{{ t('statisticsCharts.title') }}</h2>
      <p class="mt-1 text-sm text-slate-600">{{ t('statisticsCharts.hint') }}</p>
    </div>

    <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>
    <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>

    <div v-if="charts" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
      <div v-for="kpi in kpis" :key="kpi.key" class="rounded-xl border border-slate-200 bg-white p-4">
        <p class="text-xs text-slate-500">{{ t(`statisticsCharts.kpis.${kpi.key}`) }}</p>
        <p class="mt-1 text-2xl font-semibold text-[var(--rdp-forest)]">{{ kpi.value }}</p>
      </div>
    </div>

    <div v-if="charts" class="grid gap-4 lg:grid-cols-2">
      <section class="rounded-xl border border-slate-200 bg-white p-5">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
          <h3 class="font-semibold text-[var(--rdp-forest)]">{{ t('statisticsCharts.donutTitle') }}</h3>
          <select v-model="donutSource" class="rounded border border-slate-300 bg-white px-3 py-1.5 text-sm">
            <option value="gender">{{ t('statisticsMembers.gender') }}</option>
            <option value="type">{{ t('statisticsMembers.type') }}</option>
            <option value="subscription">{{ t('statisticsMembers.duesLabel') }}</option>
            <option value="status">{{ t('statisticsMembers.status') }}</option>
          </select>
        </div>
        <DonutChart
          :items="donutItems"
          :center-label="t('statisticsCharts.membersTotal')"
          :empty-label="t('statisticsCharts.empty')"
        />
      </section>

      <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h3 class="mb-4 font-semibold text-[var(--rdp-forest)]">{{ t('statisticsCharts.barTitle') }}</h3>
        <BarChart :items="barItems" :empty-label="t('statisticsCharts.empty')" />
      </section>
    </div>

    <section v-if="charts" class="rounded-xl border border-slate-200 bg-white p-5">
      <h3 class="mb-4 font-semibold text-[var(--rdp-forest)]">{{ t('statisticsCharts.areaTitle') }}</h3>
      <AreaChart :items="areaItems" :empty-label="t('statisticsCharts.empty')" />
    </section>
  </div>
</template>
