<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PageHero from '@/components/public/PageHero.vue'
import { newsItems } from '@/data/publicContent'
import { fetchPublicNewsItem } from '@/services/content'

const route = useRoute()
const { t, locale } = useI18n()
const apiItem = ref(null)
const loading = ref(true)

const staticItem = computed(() => newsItems.find((n) => n.slug === route.params.slug))

const item = computed(() => {
  if (apiItem.value) {
    return {
      title: { ar: apiItem.value.title_ar, fr: apiItem.value.title_fr },
      content: { ar: apiItem.value.content_ar, fr: apiItem.value.content_fr },
      image: apiItem.value.image_url || '/logo.png',
      date: (apiItem.value.published_at || '').slice(0, 10),
    }
  }
  return staticItem.value
})

const localized = (value) => value?.[locale.value] || value?.en || value?.fr || value?.ar || ''

async function load(slug) {
  loading.value = true
  apiItem.value = null
  try {
    apiItem.value = await fetchPublicNewsItem(slug)
  } catch {
    apiItem.value = null
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

onMounted(() => {})
</script>

<template>
  <div v-if="item">
    <PageHero :title="localized(item.title)" :subtitle="item.date" />
    <section class="mx-auto max-w-3xl px-5 py-12 md:px-8">
      <img :src="item.image" alt="" class="mb-6 h-72 w-full rounded-xl object-cover" />
      <p class="whitespace-pre-line leading-relaxed text-slate-700">
        {{ localized(item.content || item.excerpt) }}
      </p>
    </section>
  </div>
  <div v-else-if="!loading" class="mx-auto max-w-3xl px-5 py-20">{{ t('pages.notFound') }}</div>
</template>
