<script setup>
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PageHero from '@/components/public/PageHero.vue'
import { newsItems } from '@/data/publicContent'

const { t, locale } = useI18n()
const localized = (item) => item?.[locale.value] || item?.fr || ''
</script>

<template>
  <div>
    <PageHero :title="t('nav.news')" :subtitle="t('pages.news.subtitle')" />
    <section class="mx-auto grid max-w-6xl gap-6 px-5 py-12 md:grid-cols-3 md:px-8">
      <article v-for="item in newsItems" :key="item.id" class="overflow-hidden rounded-xl bg-white shadow-sm">
        <img :src="item.image" alt="" class="h-44 w-full object-cover" />
        <div class="space-y-2 p-5">
          <p class="text-xs text-slate-500">{{ item.date }}</p>
          <h2 class="text-lg font-semibold">{{ localized(item.title) }}</h2>
          <p class="text-sm text-slate-600">{{ localized(item.excerpt) }}</p>
          <RouterLink :to="`/news/${item.slug}`" class="text-sm font-semibold text-[var(--rdp-forest)] hover:underline">
            {{ t('home.readMore') }}
          </RouterLink>
        </div>
      </article>
    </section>
  </div>
</template>
