<script setup>
import { computed, useId } from 'vue'

const props = defineProps({
  items: { type: Array, default: () => [] },
  emptyLabel: { type: String, default: '—' },
  color: { type: String, default: '#1f4d3a' },
  height: { type: Number, default: 260 },
})

const gradientId = `stats-area-${useId().replace(/:/g, '')}`
const pad = { top: 20, right: 16, bottom: 36, left: 36 }
const width = 640
const innerW = width - pad.left - pad.right
const innerH = computed(() => props.height - pad.top - pad.bottom)
const max = computed(() => Math.max(...props.items.map((item) => Number(item.value || 0)), 1))

const points = computed(() => {
  if (!props.items.length) return []
  const step = props.items.length === 1 ? 0 : innerW / (props.items.length - 1)
  return props.items.map((item, index) => {
    const x = pad.left + index * step
    const y = pad.top + innerH.value - (Number(item.value) / max.value) * innerH.value
    return { ...item, x, y, value: Number(item.value) }
  })
})

const linePath = computed(() => {
  if (!points.value.length) return ''
  return points.value.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`).join(' ')
})

const areaPath = computed(() => {
  if (!points.value.length) return ''
  const first = points.value[0]
  const last = points.value[points.value.length - 1]
  const baseline = pad.top + innerH.value
  return `${linePath.value} L ${last.x} ${baseline} L ${first.x} ${baseline} Z`
})
</script>

<template>
  <div v-if="!items.length" class="text-sm text-slate-500">{{ emptyLabel }}</div>
  <div v-else class="overflow-x-auto">
    <svg :width="width" :height="height" :viewBox="`0 0 ${width} ${height}`" role="img" class="max-w-full">
      <defs>
        <linearGradient :id="gradientId" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" :stop-color="color" stop-opacity="0.28" />
          <stop offset="100%" :stop-color="color" stop-opacity="0.02" />
        </linearGradient>
      </defs>
      <line
        v-for="tick in 4"
        :key="tick"
        :x1="pad.left"
        :x2="width - pad.right"
        :y1="pad.top + (innerH / 3) * (tick - 1)"
        :y2="pad.top + (innerH / 3) * (tick - 1)"
        stroke="#e2e8f0"
      />
      <path :d="areaPath" :fill="`url(#${gradientId})`" />
      <path :d="linePath" fill="none" :stroke="color" stroke-width="2.5" stroke-linejoin="round" />
      <g v-for="point in points" :key="point.key">
        <circle :cx="point.x" :cy="point.y" r="4" :fill="color">
          <title>{{ point.label }} — {{ point.value }}</title>
        </circle>
        <text
          :x="point.x"
          :y="height - 12"
          text-anchor="middle"
          class="fill-slate-500"
          font-size="10"
        >
          {{ point.label }}
        </text>
      </g>
      <text :x="8" :y="pad.top + 4" class="fill-slate-500" font-size="10">{{ max }}</text>
      <text :x="8" :y="pad.top + innerH" class="fill-slate-500" font-size="10">0</text>
    </svg>
  </div>
</template>
