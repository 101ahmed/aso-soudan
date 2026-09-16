<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { geocodeQuery, googleSearchHref, mapQuery, osmEmbedSrc, osmSearchHref } from '@/utils/mapUrl'

const props = defineProps({
  url: { type: String, default: '' },
  location: { type: String, default: '' },
})

const { t } = useI18n()
const query = computed(() => mapQuery(props.url, props.location))
const osmHref = computed(() => osmSearchHref(props.url, props.location))
const googleHref = computed(() => googleSearchHref(props.url, props.location))
const embed = ref('')
const label = ref('')
const loading = ref(false)

let requestId = 0

watch(
  query,
  async (value) => {
    const id = ++requestId
    embed.value = ''
    label.value = value
    if (!value) return
    loading.value = true
    try {
      const hit = await geocodeQuery(value)
      if (id !== requestId) return
      if (hit) {
        embed.value = osmEmbedSrc(hit.lat, hit.lon)
        label.value = hit.label || value
      }
    } catch {
      if (id !== requestId) return
    } finally {
      if (id === requestId) loading.value = false
    }
  },
  { immediate: true },
)
</script>

<template>
  <div v-if="query" class="mt-3 overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
    <iframe
      v-if="embed"
      :src="embed"
      :title="t('parents.mapLink')"
      class="h-52 w-full border-0 bg-slate-100"
      loading="lazy"
      referrerpolicy="no-referrer-when-downgrade"
    />
    <p v-else-if="loading" class="px-3 py-8 text-center text-xs text-slate-500">
      {{ t('parents.mapLoading') }}
    </p>
    <p v-else class="px-3 py-4 text-center text-xs text-slate-500">
      {{ t('parents.mapFallback') }}
    </p>
    <div class="space-y-2 border-t border-slate-200 bg-white px-3 py-2">
      <p class="text-xs text-slate-600">{{ label }}</p>
      <div class="flex flex-wrap gap-3">
        <a
          :href="osmHref"
          target="_blank"
          rel="noopener noreferrer"
          class="text-sm font-semibold text-teal-800 hover:underline"
        >
          {{ t('parents.mapOsm') }}
        </a>
        <a
          :href="googleHref"
          target="_blank"
          rel="noopener noreferrer"
          class="text-sm font-semibold text-slate-700 hover:underline"
        >
          {{ t('parents.mapGoogle') }}
        </a>
      </div>
    </div>
  </div>
</template>
