<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import SectionHeading from '@/components/public/SectionHeading.vue'
import {
  galleryAlbums,
  newsItems,
  orgUnits,
  publicStats,
  recentActivities,
  upcomingEvents,
} from '@/data/publicContent'

const { t, locale } = useI18n()

function localized(item) {
  return item?.[locale.value] || item?.fr || item?.ar || ''
}

const latestNews = computed(() => newsItems.slice(0, 3))
</script>

<template>
  <div>
    <section class="relative min-h-[100svh] overflow-hidden">
      <div class="absolute inset-0">
        <img
          class="hero-media h-full w-full object-cover"
          src="https://images.unsplash.com/photo-1529156069898-49953e39b3ac?auto=format&fit=crop&w=2000&q=80"
          alt=""
        />
        <div class="absolute inset-0 bg-[linear-gradient(115deg,rgba(18,40,28,0.9)_8%,rgba(18,40,28,0.62)_52%,rgba(18,40,28,0.35)_100%)]" />
      </div>

      <div class="hero-copy relative z-10 mx-auto flex min-h-[100svh] max-w-6xl flex-col justify-end px-5 pb-16 pt-28 md:px-8 md:pb-24">
        <div class="mb-5">
          <img
            src="/logo.png"
            :alt="t('app.name')"
            class="h-24 w-auto rounded-xl bg-white/95 object-contain px-3 py-2 shadow-lg md:h-28"
          />
          <p class="mt-4 text-sm font-semibold tracking-[0.16em] text-[var(--rdp-gold)] uppercase">
            Rennes · France
          </p>
        </div>
        <h1 class="max-w-4xl font-[family-name:var(--font-display)] text-4xl leading-[1.12] font-bold text-white md:text-6xl">
          {{ t('app.name') }}
        </h1>
        <p class="mt-4 text-base font-semibold tracking-wide text-[var(--rdp-gold)] md:text-lg">
          {{ t('app.tagline') }}
        </p>
        <p class="mt-1 text-sm text-white/75">
          {{ t('app.motto') }}
        </p>
        <p class="mt-4 max-w-2xl text-lg text-white/92 md:text-xl">
          {{ t('home.welcome') }}
        </p>
        <p class="mt-3 max-w-2xl text-base text-white/80 md:text-lg">
          {{ t('home.lead') }}
        </p>
        <div class="mt-8 flex flex-wrap gap-3">
          <RouterLink
            to="/about"
            class="inline-flex rounded bg-[var(--rdp-gold)] px-5 py-3 text-sm font-semibold text-[var(--rdp-ink)]"
          >
            {{ t('home.ctaAbout') }}
          </RouterLink>
          <RouterLink
            to="/register/student"
            class="inline-flex rounded border border-white/75 px-5 py-3 text-sm font-semibold text-white hover:bg-white/10"
          >
            {{ t('home.ctaStudent') }}
          </RouterLink>
          <RouterLink
            to="/register/member"
            class="inline-flex rounded border border-white/75 px-5 py-3 text-sm font-semibold text-white hover:bg-white/10"
          >
            {{ t('home.ctaMember') }}
          </RouterLink>
        </div>
      </div>
    </section>

    <section class="mx-auto max-w-6xl px-5 py-16 md:px-8">
      <SectionHeading :title="t('home.aboutTitle')" :subtitle="t('home.aboutText')" />
      <RouterLink to="/about" class="inline-flex text-sm font-semibold text-[var(--rdp-forest)] underline-offset-4 hover:underline">
        {{ t('home.readMore') }}
      </RouterLink>
    </section>

    <section class="bg-white/70 py-16">
      <div class="mx-auto max-w-6xl px-5 md:px-8">
        <SectionHeading :title="t('home.orgTitle')" :subtitle="t('home.orgSubtitle')" />
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
          <RouterLink
            v-for="unit in orgUnits"
            :key="unit.slug"
            :to="unit.path"
            class="rounded-lg border border-[var(--rdp-forest)]/15 bg-[var(--rdp-cream)] px-4 py-4 text-sm font-medium text-[var(--rdp-forest)] transition hover:border-[var(--rdp-forest)]/40 hover:bg-white"
          >
            {{ t(unit.nameKey) }}
          </RouterLink>
        </div>
      </div>
    </section>

    <section class="mx-auto max-w-6xl px-5 py-16 md:px-8">
      <div class="mb-8 flex items-end justify-between gap-4">
        <SectionHeading :title="t('home.newsTitle')" class="mb-0" />
        <RouterLink to="/news" class="text-sm font-semibold text-[var(--rdp-forest)] hover:underline">
          {{ t('home.seeAll') }}
        </RouterLink>
      </div>
      <div class="grid gap-6 md:grid-cols-3">
        <article v-for="item in latestNews" :key="item.id" class="overflow-hidden rounded-xl bg-white shadow-sm">
          <img :src="item.image" alt="" class="h-44 w-full object-cover" />
          <div class="space-y-2 p-5">
            <p class="text-xs text-slate-500">{{ item.date }}</p>
            <h3 class="text-lg font-semibold text-[var(--rdp-ink)]">{{ localized(item.title) }}</h3>
            <p class="text-sm text-slate-600">{{ localized(item.excerpt) }}</p>
            <RouterLink :to="`/news/${item.slug}`" class="inline-flex text-sm font-semibold text-[var(--rdp-forest)] hover:underline">
              {{ t('home.readMore') }}
            </RouterLink>
          </div>
        </article>
      </div>
    </section>

    <section class="bg-[var(--rdp-forest)] py-16 text-white">
      <div class="mx-auto max-w-6xl px-5 md:px-8">
        <div class="mb-8 flex items-end justify-between gap-4">
          <div>
            <h2 class="font-[family-name:var(--font-display)] text-3xl font-bold md:text-4xl">{{ t('home.eventsTitle') }}</h2>
            <p class="mt-2 text-white/80">{{ t('home.eventsSubtitle') }}</p>
          </div>
          <RouterLink to="/events" class="text-sm font-semibold text-[var(--rdp-gold)] hover:underline">
            {{ t('home.seeAll') }}
          </RouterLink>
        </div>
        <div class="grid gap-6 md:grid-cols-2">
          <article v-for="event in upcomingEvents" :key="event.id" class="overflow-hidden rounded-xl bg-white/10">
            <img :src="event.image" alt="" class="h-48 w-full object-cover" />
            <div class="space-y-2 p-5">
              <h3 class="text-xl font-semibold">{{ localized(event.title) }}</h3>
              <p class="text-sm text-white/80">
                {{ event.date }} · {{ event.time }} · {{ localized(event.place) }}
              </p>
              <p class="text-sm text-white/70">{{ localized(event.organizer) }}</p>
              <p class="text-sm text-white/85">{{ localized(event.summary) }}</p>
              <div class="flex flex-wrap gap-3 pt-2">
                <RouterLink :to="`/events/${event.slug}`" class="text-sm font-semibold text-[var(--rdp-gold)] hover:underline">
                  {{ t('home.eventDetails') }}
                </RouterLink>
                <RouterLink
                  v-if="event.registrationOpen"
                  :to="`/events/${event.slug}#register`"
                  class="text-sm font-semibold text-white hover:underline"
                >
                  {{ t('home.eventRegister') }}
                </RouterLink>
              </div>
            </div>
          </article>
        </div>
      </div>
    </section>

    <section class="mx-auto max-w-6xl px-5 py-16 md:px-8">
      <SectionHeading :title="t('home.statsTitle')" :subtitle="t('home.statsSubtitle')" />
      <div class="grid grid-cols-2 gap-4 md:grid-cols-5">
        <div v-for="stat in publicStats" :key="stat.key" class="rounded-xl bg-white px-4 py-6 text-center shadow-sm">
          <p class="text-3xl font-bold text-[var(--rdp-forest)]">{{ stat.value }}</p>
          <p class="mt-2 text-sm text-slate-600">{{ t(`home.stats.${stat.key}`) }}</p>
        </div>
      </div>
    </section>

    <section class="bg-white/70 py-16">
      <div class="mx-auto max-w-6xl px-5 md:px-8">
        <SectionHeading :title="t('home.activitiesTitle')" />
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <div v-for="activity in recentActivities" :key="activity.key" class="overflow-hidden rounded-xl">
            <img :src="activity.image" alt="" class="h-40 w-full object-cover" />
            <p class="bg-[var(--rdp-forest)] px-4 py-3 text-sm font-medium text-white">
              {{ t(`home.activities.${activity.key}`) }}
            </p>
          </div>
        </div>
      </div>
    </section>

    <section class="mx-auto max-w-6xl px-5 py-16 md:px-8">
      <div class="mb-8 flex items-end justify-between gap-4">
        <SectionHeading :title="t('home.galleryTitle')" class="mb-0" />
        <RouterLink to="/gallery" class="text-sm font-semibold text-[var(--rdp-forest)] hover:underline">
          {{ t('home.galleryCta') }}
        </RouterLink>
      </div>
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <RouterLink
          v-for="album in galleryAlbums"
          :key="album.slug"
          :to="`/gallery/${album.slug}`"
          class="group overflow-hidden rounded-xl"
        >
          <img :src="album.cover" alt="" class="h-44 w-full object-cover transition duration-500 group-hover:scale-105" />
          <p class="mt-2 text-sm font-medium text-[var(--rdp-forest)]">{{ localized(album.title) }}</p>
        </RouterLink>
      </div>
    </section>

    <section class="border-y border-[var(--rdp-forest)]/10 bg-[linear-gradient(120deg,#f7f3ea,#e7ece8)] py-16">
      <div class="mx-auto flex max-w-6xl flex-col gap-6 px-5 md:flex-row md:items-center md:justify-between md:px-8">
        <div>
          <h2 class="font-[family-name:var(--font-display)] text-3xl font-bold text-[var(--rdp-forest)]">
            {{ t('home.registerTitle') }}
          </h2>
          <p class="mt-2 max-w-xl text-slate-700">{{ t('home.registerSubtitle') }}</p>
        </div>
        <div class="flex flex-wrap gap-3">
          <RouterLink to="/register/student" class="rounded bg-[var(--rdp-forest)] px-5 py-3 text-sm font-semibold text-white">
            {{ t('home.ctaStudent') }}
          </RouterLink>
          <RouterLink to="/register/member" class="rounded border border-[var(--rdp-forest)] px-5 py-3 text-sm font-semibold text-[var(--rdp-forest)]">
            {{ t('home.ctaMember') }}
          </RouterLink>
        </div>
      </div>
    </section>

    <footer class="bg-[var(--rdp-forest)] text-white">
      <div class="mx-auto grid max-w-6xl gap-8 px-5 py-12 md:grid-cols-3 md:px-8">
        <div>
          <div class="flex items-center gap-3">
            <img src="/logo.png" :alt="t('app.name')" class="h-14 w-auto rounded-md bg-white object-contain px-2 py-1" />
            <p class="font-semibold">{{ t('app.name') }}</p>
          </div>
          <p class="mt-3 text-sm text-white/75">{{ t('home.footerPlace') }}</p>
        </div>
        <div class="text-sm text-white/80">
          <p class="mb-2 font-semibold text-white">{{ t('nav.contact') }}</p>
          <RouterLink to="/contact" class="hover:text-[var(--rdp-gold)]">{{ t('home.footerContact') }}</RouterLink>
        </div>
        <div class="text-sm text-white/70">
          <p>{{ t('home.footerNote') }}</p>
          <RouterLink to="/login" class="mt-3 inline-flex text-[var(--rdp-gold)] hover:underline">
            {{ t('nav.login') }}
          </RouterLink>
        </div>
      </div>
    </footer>
  </div>
</template>
