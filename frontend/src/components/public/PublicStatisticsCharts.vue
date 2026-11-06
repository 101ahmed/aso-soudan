<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import DonutChart from '@/components/charts/DonutChart.vue'
import BarChart from '@/components/charts/BarChart.vue'
import AreaChart from '@/components/charts/AreaChart.vue'

const PALETTE = ['#1f4d3a', '#c4a35a', '#0f766e', '#be123c', '#0369a1', '#b45309', '#7c3aed', '#64748b']

const props = defineProps({
  stats: { type: Object, default: null },
})

const { t, locale } = useI18n()

function colorize(rows, labelFor) {
  return (rows || [])
    .map((row, index) => ({
      key: row.key,
      value: Number(row.total || 0),
      label: labelFor(row.key),
      color: PALETTE[index % PALETTE.length],
    }))
}

const donutItems = computed(() =>
  colorize(props.stats?.charts?.community, (key) => t(`secretariat.statLabels.${key}`)),
)

const barItems = computed(() =>
  colorize(props.stats?.charts?.activity, (key) => t(`secretariat.statLabels.${key}`)),
)

const monthFormatter = computed(
  () => new Intl.DateTimeFormat(locale.value === 'ar' ? 'ar' : locale.value, { month: 'short' }),
)

const areaItems = computed(() =>
  (props.stats?.charts?.monthly || []).map((row) => {
    const [year, month] = String(row.key).split('-')
    const date = new Date(Number(year), Number(month) - 1, 1)
    return {
      key: row.key,
      value: Number(row.total || 0),
      label: monthFormatter.value.format(date),
    }
  }),
)
</script>

<template>
  <div class="space-y-4">
    <div class="grid gap-4 lg:grid-cols-2">
      <section class="rounded-xl border border-[var(--rdp-forest)]/10 bg-white p-5 shadow-sm">
        <h3 class="mb-4 font-semibold text-[var(--rdp-forest)]">{{ t('statisticsCharts.publicDonutTitle') }}</h3>
        <DonutChart
          :items="donutItems"
          :center-label="t('statisticsCharts.publicDonutCenter')"
          :empty-label="t('statisticsCharts.empty')"
        />
      </section>
      <section class="rounded-xl border border-[var(--rdp-forest)]/10 bg-white p-5 shadow-sm">
        <h3 class="mb-4 font-semibold text-[var(--rdp-forest)]">{{ t('statisticsCharts.publicBarTitle') }}</h3>
        <BarChart :items="barItems" :empty-label="t('statisticsCharts.empty')" />
      </section>
    </div>
    <section class="rounded-xl border border-[var(--rdp-forest)]/10 bg-white p-5 shadow-sm">
      <h3 class="mb-4 font-semibold text-[var(--rdp-forest)]">{{ t('statisticsCharts.publicAreaTitle') }}</h3>
      <AreaChart :items="areaItems" :empty-label="t('statisticsCharts.empty')" />
    </section>
  </div>
</template>
