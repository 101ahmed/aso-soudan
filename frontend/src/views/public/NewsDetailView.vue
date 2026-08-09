<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PageHero from '@/components/public/PageHero.vue'
import { newsItems } from '@/data/publicContent'

const route = useRoute()
const { t, locale } = useI18n()
const item = computed(() => newsItems.find((n) => n.slug === route.params.slug))
const localized = (value) => value?.[locale.value] || value?.fr || ''
</script>

<template>
  <div v-if="item">
    <PageHero :title="localized(item.title)" :subtitle="item.date" />
    <section class="mx-auto max-w-3xl px-5 py-12 md:px-8">
      <img :src="item.image" alt="" class="mb-6 h-72 w-full rounded-xl object-cover" />
      <p class="leading-relaxed text-slate-700">{{ localized(item.excerpt) }}</p>
      <p class="mt-4 leading-relaxed text-slate-700">{{ t('pages.news.detailExtra') }}</p>
    </section>
  </div>
  <div v-else class="mx-auto max-w-3xl px-5 py-20">{{ t('pages.notFound') }}</div>
</template>
