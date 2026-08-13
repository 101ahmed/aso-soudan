<script setup>
import { useI18n } from 'vue-i18n'
import { applyDocumentDirection } from '@/i18n'
import { LOCALES } from '@/utils/localized'

defineProps({
  isHome: { type: Boolean, default: false },
  variant: { type: String, default: 'public' },
})

const { locale } = useI18n()

function setLocale(next) {
  locale.value = next
  localStorage.setItem('rdp_locale', next)
  applyDocumentDirection(next)
}

function buttonClass(code, isHome, variant) {
  const active = locale.value === code
  if (variant === 'admin') {
    return active ? 'bg-teal-800 text-white' : 'bg-white'
  }
  if (active) {
    return isHome ? 'bg-white text-[var(--rdp-forest)]' : 'bg-[var(--rdp-forest)] text-white'
  }
  return isHome ? 'text-white' : ''
}
</script>

<template>
  <div
    class="flex overflow-hidden rounded text-xs"
    :class="variant === 'admin'
      ? 'rounded-md border border-slate-300 text-sm'
      : (isHome ? 'border border-white/35' : 'border border-slate-300')"
  >
    <button
      v-for="code in LOCALES"
      :key="code"
      type="button"
      class="px-2 py-1 uppercase"
      :class="[variant === 'admin' ? 'px-2.5 py-1' : '', buttonClass(code, isHome, variant)]"
      @click="setLocale(code)"
    >
      {{ code }}
    </button>
  </div>
</template>
