<script setup>
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PageHero from '@/components/public/PageHero.vue'
import { upcomingEvents } from '@/data/publicContent'

const { t, locale } = useI18n()
const localized = (item) => item?.[locale.value] || item?.fr || ''
</script>

<template>
  <div>
    <PageHero :title="t('nav.events')" :subtitle="t('pages.events.subtitle')" />
    <section class="mx-auto grid max-w-6xl gap-6 px-5 py-12 md:grid-cols-2 md:px-8">
      <article v-for="event in upcomingEvents" :key="event.id" class="overflow-hidden rounded-xl bg-white shadow-sm">
        <img :src="event.image" alt="" class="h-48 w-full object-cover" />
        <div class="space-y-2 p-5">
          <h2 class="text-xl font-semibold">{{ localized(event.title) }}</h2>
          <p class="text-sm text-slate-600">{{ event.date }} · {{ event.time }} · {{ localized(event.place) }}</p>
          <p class="text-sm text-slate-500">{{ localized(event.organizer) }}</p>
          <p class="text-sm text-slate-700">{{ localized(event.summary) }}</p>
          <RouterLink :to="`/events/${event.slug}`" class="text-sm font-semibold text-[var(--rdp-forest)] hover:underline">
            {{ t('home.eventDetails') }}
          </RouterLink>
        </div>
      </article>
    </section>
  </div>
</template>
