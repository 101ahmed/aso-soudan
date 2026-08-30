<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { mapEmbedSrc, mapHref } from '@/utils/mapUrl'

const props = defineProps({
  url: { type: String, default: '' },
  location: { type: String, default: '' },
})

const { t } = useI18n()
const href = computed(() => mapHref(props.url, props.location))
const embed = computed(() => mapEmbedSrc(props.url, props.location))

function openMap(event) {
  if (!href.value) return
  event.preventDefault()
  window.open(href.value, '_blank', 'noopener,noreferrer')
}
</script>

<template>
  <div v-if="href" class="mt-3 space-y-2">
    <iframe
      v-if="embed"
      :src="embed"
      :title="t('parents.mapLink')"
      class="h-48 w-full rounded-xl border border-slate-200 bg-slate-100"
      loading="lazy"
      referrerpolicy="no-referrer-when-downgrade"
      allowfullscreen
    />
    <a
      :href="href"
      target="_blank"
      rel="noopener noreferrer"
      class="inline-flex text-sm font-semibold text-teal-800 hover:underline"
      @click="openMap"
    >
      {{ t('parents.mapLink') }}
    </a>
  </div>
</template>
