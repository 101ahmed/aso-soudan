<script setup>
import { computed, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PageHero from '@/components/public/PageHero.vue'
import { pickContent, pickTitle } from '@/utils/localized'
import { fetchPublicMediaCenterItem } from '@/services/mediaCenter'

const KIND_ICONS = {
  official: '📄',
  statement: '🎙️',
  coverage: '📰',
  conference: '🎤',
  interview: '💬',
}

const route = useRoute()
const { t, locale } = useI18n()
const item = ref(null)
const loading = ref(true)

const title = computed(() => (item.value ? pickTitle(item.value, locale.value) : ''))
const content = computed(() => (item.value ? pickContent(item.value, locale.value) : ''))
const source = computed(() => {
  if (!item.value) return ''
  return locale.value === 'ar' ? item.value.source_ar || item.value.source_fr : item.value.source_fr || item.value.source_ar
})
const person = computed(() => {
  if (!item.value) return ''
  return locale.value === 'ar' ? item.value.person_ar || item.value.person_fr : item.value.person_fr || item.value.person_ar
})
const location = computed(() => {
  if (!item.value) return ''
  return locale.value === 'ar' ? item.value.location_ar || item.value.location_fr : item.value.location_fr || item.value.location_ar
})

async function load(slug) {
  loading.value = true
  item.value = null
  try {
    item.value = await fetchPublicMediaCenterItem(slug)
  } catch {
    item.value = null
  } finally {
    loading.value = false
  }
}

watch(
  () => route.params.slug,
  (slug) => {
    if (slug) load(slug)
  },
  { immediate: true },
)
</script>

<template>
  <div v-if="item">
    <PageHero :title="title" :subtitle="t(`mediaCenter.kinds.${item.kind}`)" />
    <section class="mx-auto max-w-3xl space-y-5 px-5 py-12 md:px-8">
      <p class="text-sm text-slate-500">
        {{ KIND_ICONS[item.kind] }} {{ t(`mediaCenter.kinds.${item.kind}`) }}
        <span v-if="item.occurred_on"> · {{ item.occurred_on }}</span>
      </p>
      <img v-if="item.image_url" :src="item.image_url" alt="" class="h-72 w-full rounded-xl object-cover" />
      <p v-if="person" class="text-sm text-slate-700">👤 {{ person }}</p>
      <p v-if="source" class="text-sm text-slate-700">📰 {{ source }}</p>
      <p v-if="location" class="text-sm text-slate-700">📍 {{ location }}</p>
      <p class="whitespace-pre-line leading-relaxed text-slate-700">{{ content }}</p>
      <a
        v-if="item.external_url"
        :href="item.external_url"
        target="_blank"
        rel="noreferrer"
        class="inline-flex text-sm font-semibold text-[var(--rdp-forest)] hover:underline"
      >
        {{ t('mediaCenter.openSource') }}
      </a>
      <div>
        <RouterLink to="/media-center" class="text-sm font-semibold text-[var(--rdp-forest)] hover:underline">
          ← {{ t('mediaCenter.publicTitle') }}
        </RouterLink>
      </div>
    </section>
  </div>
  <div v-else-if="!loading" class="mx-auto max-w-3xl px-5 py-20">{{ t('pages.notFound') }}</div>
</template>
