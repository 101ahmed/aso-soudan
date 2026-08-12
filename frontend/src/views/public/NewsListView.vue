<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PageHero from '@/components/public/PageHero.vue'
import { newsItems } from '@/data/publicContent'
import { fetchPublicNews } from '@/services/content'

const { t, locale } = useI18n()
const items = ref([])

const localizedTitle = (item) =>
  item.title_ar
    ? locale.value === 'ar'
      ? item.title_ar
      : item.title_fr
    : item.title?.[locale.value] || item.title?.fr || ''

const localizedExcerpt = (item) => {
  if (item.content_ar || item.content_fr) {
    const text = locale.value === 'ar' ? item.content_ar : item.content_fr
    return (text || '').slice(0, 140)
  }
  return item.excerpt?.[locale.value] || item.excerpt?.fr || ''
}

onMounted(async () => {
  try {
    const data = await fetchPublicNews({ per_page: 24 })
    const apiItems = data.data || []
    items.value = apiItems.length
      ? apiItems.map((item) => ({
          ...item,
          image: item.image_url || '/logo.png',
          date: (item.published_at || '').slice(0, 10),
        }))
      : newsItems
  } catch {
    items.value = newsItems
  }
})
</script>

<template>
  <div>
    <PageHero :title="t('nav.news')" :subtitle="t('pages.news.subtitle')" />
    <section class="mx-auto grid max-w-6xl gap-6 px-5 py-12 md:grid-cols-3 md:px-8">
      <article v-for="item in items" :key="item.id" class="overflow-hidden rounded-xl bg-white shadow-sm">
        <img :src="item.image" alt="" class="h-44 w-full object-cover" />
        <div class="space-y-2 p-5">
          <p class="text-xs text-slate-500">{{ item.date }}</p>
          <h2 class="text-lg font-semibold">{{ localizedTitle(item) }}</h2>
          <p class="text-sm text-slate-600">{{ localizedExcerpt(item) }}</p>
          <RouterLink :to="`/news/${item.slug}`" class="text-sm font-semibold text-[var(--rdp-forest)] hover:underline">
            {{ t('home.readMore') }}
          </RouterLink>
        </div>
      </article>
    </section>
  </div>
</template>
