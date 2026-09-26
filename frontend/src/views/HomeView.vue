<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import SectionHeading from '@/components/public/SectionHeading.vue'
import PhotoCarousel from '@/components/public/PhotoCarousel.vue'
import {
  galleryAlbums,
  newsItems,
  orgUnits,
  publicStats,
  recentActivities,
} from '@/data/publicContent'
import { fetchPublicAlbums, fetchPublicAnnouncements, fetchPublicDecisions, fetchPublicEvents, fetchPublicNews, fetchPublicStats, mapPublicEvent } from '@/services/content'
import { albumsToSlides } from '@/utils/gallerySlides'
import { mergePublicStats } from '@/utils/publicStats'

const { t, locale } = useI18n()
const apiNews = ref([])
const apiAnnouncements = ref([])
const apiAlbums = ref([])
const apiEvents = ref([])
const apiDecisions = ref([])
const eventsLoaded = ref(false)
const liveStats = ref(null)

function localized(item) {
  return item?.[locale.value] || item?.en || item?.fr || item?.ar || ''
}

const latestNews = computed(() => {
  if (apiNews.value.length) {
    return apiNews.value.slice(0, 3).map((item) => ({
      id: item.id,
      slug: item.slug,
      image: item.image_url || '/logo.png',
      date: (item.published_at || '').slice(0, 10),
      title: { ar: item.title_ar, fr: item.title_fr },
      excerpt: {
        ar: (item.content_ar || '').slice(0, 120),
        fr: (item.content_fr || '').slice(0, 120),
      },
    }))
  }
  return newsItems.slice(0, 3)
})

const homeAnnouncements = computed(() =>
  apiAnnouncements.value.slice(0, 4).map((item) => ({
    id: item.id,
    image: item.image_url || '/logo.png',
    title: { ar: item.title_ar, fr: item.title_fr },
    excerpt: {
      ar: (item.content_ar || '').slice(0, 140),
      fr: (item.content_fr || '').slice(0, 140),
    },
  })),
)

const homeSlides = computed(() => {
  const source = apiAlbums.value.length ? apiAlbums.value : galleryAlbums
  const slides = albumsToSlides(source, locale.value)
  return slides.length ? slides : albumsToSlides(galleryAlbums, locale.value)
})

const homeEvents = computed(() => apiEvents.value.map(mapPublicEvent).filter(Boolean))

const homeDecisions = computed(() =>
  apiDecisions.value.slice(0, 6).map((item) => ({
    id: item.id,
    date: item.decided_on || '',
    kind: item.kind,
    title: { ar: item.title_ar, fr: item.title_fr },
    excerpt: {
      ar: (item.details_ar || '').slice(0, 180),
      fr: (item.details_fr || '').slice(0, 180),
    },
  })),
)

const displayedStats = computed(() =>
  mergePublicStats(publicStats, liveStats.value, { teachers: 'teachers_and_volunteers' }),
)

onMounted(async () => {
  try {
    const data = await fetchPublicNews({ home: 1, per_page: 6 })
    const homeItems = data.data || []
    if (homeItems.length) {
      apiNews.value = homeItems
    } else {
      const featured = await fetchPublicNews({ featured: 1, per_page: 6 })
      apiNews.value = featured.data || []
    }
  } catch {
    apiNews.value = []
  }

  try {
    const data = await fetchPublicAnnouncements({ home: 1, per_page: 6 })
    apiAnnouncements.value = data.data || []
  } catch {
    apiAnnouncements.value = []
  }

  try {
    const albums = await fetchPublicAlbums({ home: 1, per_page: 8 })
    apiAlbums.value = albums.data?.length
      ? albums.data
      : (await fetchPublicAlbums({ per_page: 8 })).data || []
  } catch {
    apiAlbums.value = []
  }

  try {
    const data = await fetchPublicDecisions({ per_page: 6 })
    apiDecisions.value = data.data || []
  } catch {
    apiDecisions.value = []
  }

  try {
    const upcoming = await fetchPublicEvents({ upcoming: 1, per_page: 6 })
    const upcomingItems = upcoming.data || []
    if (upcomingItems.length) {
      apiEvents.value = upcomingItems
    } else {
      const latest = await fetchPublicEvents({ per_page: 6 })
      apiEvents.value = latest.data || []
    }
  } catch {
    apiEvents.value = []
  } finally {
    eventsLoaded.value = true
  }

  try {
    liveStats.value = await fetchPublicStats()
  } catch {
    liveStats.value = null
  }
})
</script>

<template>
  <div>
    <section class="relative min-h-[100svh] overflow-hidden">
      <div class="absolute inset-0">
        <img
          class="hero-media h-full w-full object-cover object-[center_42%]"
          src="/hero-home.jpg"
          :alt="t('app.name')"
        />
        <div class="absolute inset-0 bg-[linear-gradient(180deg,rgba(18,40,28,0.22)_0%,rgba(18,40,28,0.38)_42%,rgba(18,40,28,0.86)_100%)]" />
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

    <section v-if="homeDecisions.length" class="bg-[var(--rdp-cream)] py-16">
      <div class="mx-auto max-w-6xl px-5 md:px-8">
        <SectionHeading :title="t('home.decisionsTitle')" :subtitle="t('home.decisionsSubtitle')" />
        <div class="space-y-4">
          <article
            v-for="item in homeDecisions"
            :key="item.id"
            class="rounded-xl border border-[var(--rdp-forest)]/15 bg-white p-5 shadow-sm"
          >
            <p class="text-xs font-semibold tracking-wide text-[var(--rdp-forest)] uppercase">
              {{ item.date }}
            </p>
            <h3 class="mt-2 text-lg font-semibold text-[var(--rdp-ink)]">{{ localized(item.title) }}</h3>
            <p v-if="localized(item.excerpt)" class="mt-2 text-sm leading-relaxed text-slate-600">
              {{ localized(item.excerpt) }}
            </p>
          </article>
        </div>
      </div>
    </section>

    <section v-if="homeAnnouncements.length" class="bg-[var(--rdp-gold)]/15 py-16">
      <div class="mx-auto max-w-6xl px-5 md:px-8">
        <SectionHeading :title="t('home.announcementsTitle')" :subtitle="t('home.announcementsSubtitle')" />
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
          <article
            v-for="item in homeAnnouncements"
            :key="item.id"
            class="overflow-hidden rounded-xl bg-white shadow-sm"
          >
            <img :src="item.image" alt="" class="h-36 w-full object-cover" />
            <div class="space-y-2 p-4">
              <h3 class="text-base font-semibold text-[var(--rdp-ink)]">{{ localized(item.title) }}</h3>
              <p v-if="localized(item.excerpt)" class="text-sm text-slate-600">{{ localized(item.excerpt) }}</p>
            </div>
          </article>
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
          <div class="flex min-h-44 items-center justify-center bg-[var(--rdp-cream)]">
            <img :src="item.image" alt="" class="max-h-56 w-full object-contain" />
          </div>
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
          <p v-if="eventsLoaded && !homeEvents.length" class="text-sm text-white/80 md:col-span-2">
            {{ t('home.eventsEmpty') }}
          </p>
          <article v-for="event in homeEvents" :key="event.id" class="overflow-hidden rounded-xl bg-white/10">
            <img :src="event.image" alt="" class="h-48 w-full object-cover" />
            <div class="space-y-2 p-5">
              <p v-if="event.type" class="text-xs font-semibold text-[var(--rdp-gold)]">
                {{ t(`secretariat.eventTypes.${event.type}`) }}
              </p>
              <h3 class="text-xl font-semibold">{{ localized(event.title) }}</h3>
              <p class="text-sm text-white/80">
                {{ event.date }}
                <span v-if="event.time"> · {{ event.time }}</span>
                <span v-if="localized(event.place)"> · {{ localized(event.place) }}</span>
              </p>
              <p class="text-sm text-white/70">{{ localized(event.organizer) }}</p>
              <p class="text-sm text-white/85">{{ localized(event.summary) }}</p>
              <div class="flex flex-wrap gap-3 pt-2">
                <RouterLink :to="`/events/${event.slug}`" class="text-sm font-semibold text-[var(--rdp-gold)] hover:underline">
                  {{ t('home.eventDetails') }}
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
        <div v-for="stat in displayedStats" :key="stat.key" class="rounded-xl bg-white px-4 py-6 text-center shadow-sm">
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
      <PhotoCarousel :slides="homeSlides" :interval="5000" />
    </section>
  </div>
</template>
