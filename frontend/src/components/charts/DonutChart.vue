<script setup>
import { computed } from 'vue'

const props = defineProps({
  items: { type: Array, default: () => [] },
  size: { type: Number, default: 220 },
  thickness: { type: Number, default: 28 },
  centerLabel: { type: String, default: '' },
  emptyLabel: { type: String, default: '—' },
})

const radius = computed(() => (props.size / 2) - 8)
const circumference = computed(() => 2 * Math.PI * radius.value)
const total = computed(() => props.items.reduce((sum, item) => sum + Number(item.value || 0), 0))

const slices = computed(() => {
  let offset = 0
  return props.items
    .filter((item) => Number(item.value) > 0)
    .map((item) => {
      const value = Number(item.value)
      const length = total.value ? (value / total.value) * circumference.value : 0
      const slice = {
        ...item,
        value,
        dasharray: `${length} ${circumference.value - length}`,
        dashoffset: -offset,
        percent: total.value ? Math.round((value / total.value) * 100) : 0,
      }
      offset += length
      return slice
    })
})
</script>

<template>
  <div class="flex flex-col items-center gap-5 sm:flex-row sm:items-center">
    <div class="relative shrink-0" :style="{ width: `${size}px`, height: `${size}px` }">
      <svg
        :width="size"
        :height="size"
        :viewBox="`0 0 ${size} ${size}`"
        role="img"
        class="-rotate-90"
      >
        <circle
          :cx="size / 2"
          :cy="size / 2"
          :r="radius"
          fill="none"
          stroke="#e2e8f0"
          :stroke-width="thickness"
        />
        <circle
          v-for="slice in slices"
          :key="slice.key"
          :cx="size / 2"
          :cy="size / 2"
          :r="radius"
          fill="none"
          :stroke="slice.color"
          :stroke-width="thickness"
          stroke-linecap="butt"
          :stroke-dasharray="slice.dasharray"
          :stroke-dashoffset="slice.dashoffset"
        >
          <title>{{ slice.label }} — {{ slice.value }} ({{ slice.percent }}%)</title>
        </circle>
      </svg>
      <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center px-6 text-center">
        <p v-if="centerLabel" class="text-[11px] leading-tight text-slate-500">{{ centerLabel }}</p>
        <p class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ total }}</p>
      </div>
    </div>
    <div class="min-w-0 flex-1">
      <ul v-if="slices.length" class="space-y-1.5 text-sm">
        <li v-for="slice in slices" :key="slice.key" class="flex items-center gap-2">
          <span class="inline-block h-2.5 w-2.5 shrink-0 rounded-full" :style="{ background: slice.color }" />
          <span class="truncate text-slate-700">{{ slice.label }}</span>
          <span class="ms-auto font-medium text-slate-900">{{ slice.value }}</span>
          <span class="w-10 text-end text-xs text-slate-500">{{ slice.percent }}%</span>
        </li>
      </ul>
      <p v-else class="text-sm text-slate-500">{{ emptyLabel }}</p>
    </div>
  </div>
</template>
