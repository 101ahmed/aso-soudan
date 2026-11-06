<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'

const props = defineProps({
  items: { type: Array, default: () => [] },
  emptyLabel: { type: String, default: '—' },
  height: { type: Number, default: 260 },
})

const { locale } = useI18n()
const isRtl = computed(() => locale.value === 'ar')
const max = computed(() => Math.max(...props.items.map((item) => Number(item.value || 0)), 1))
const padX = 16
const padTop = 24
const padBottom = 56
const chartHeight = computed(() => props.height - padTop - padBottom)
const count = computed(() => Math.max(props.items.length, 1))
const width = computed(() => Math.max(560, padX * 2 + count.value * 72))
const slot = computed(() => (width.value - padX * 2) / count.value)
const barWidth = computed(() => Math.min(56, Math.max(22, slot.value * 0.45)))

function barX(index) {
  const visual = isRtl.value ? count.value - 1 - index : index
  return padX + visual * slot.value + (slot.value - barWidth.value) / 2
}
</script>

<template>
  <div v-if="!items.length" class="text-sm text-slate-500">{{ emptyLabel }}</div>
  <div v-else class="overflow-x-auto">
    <svg class="h-auto w-full" :viewBox="`0 0 ${width} ${height}`" role="img">
      <line
        :x1="padX"
        :x2="width - padX"
        :y1="height - padBottom"
        :y2="height - padBottom"
        stroke="#e2e8f0"
      />
      <g v-for="(item, index) in items" :key="item.key">
        <rect
          :x="barX(index)"
          :y="padTop + chartHeight - (Number(item.value) / max) * chartHeight"
          :width="barWidth"
          :height="(Number(item.value) / max) * chartHeight"
          :fill="item.color || '#1f4d3a'"
          rx="6"
        >
          <title>{{ item.label }} — {{ item.value }}</title>
        </rect>
        <text
          :x="barX(index) + barWidth / 2"
          :y="padTop + chartHeight - (Number(item.value) / max) * chartHeight - 6"
          text-anchor="middle"
          class="fill-slate-700"
          font-size="11"
        >
          {{ item.value }}
        </text>
        <text
          :x="barX(index) + barWidth / 2"
          :y="height - padBottom + 16"
          text-anchor="middle"
          class="fill-slate-600"
          font-size="10"
        >
          <tspan
            v-for="(line, lineIndex) in String(item.label).split(' ').slice(0, 2)"
            :key="lineIndex"
            :x="barX(index) + barWidth / 2"
            :dy="lineIndex === 0 ? 0 : 12"
          >
            {{ line }}
          </tspan>
        </text>
      </g>
    </svg>
  </div>
</template>
