<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PageHero from '@/components/public/PageHero.vue'
import PhotoCarousel from '@/components/public/PhotoCarousel.vue'
import { upcomingEvents } from '@/data/publicContent'
import { fetchPublicEvents } from '@/services/content'
import { imagesToSlides } from '@/utils/gallerySlides'

const { t, locale } = useI18n()
const localized = (item) => item?.[locale.value] || item?.en || item?.fr || item?.ar || ''
const apiEvents = ref([])
const loaded = ref(false)

const events = computed(() => {
  if (loaded.value && apiEvents.value.length) {
    return apiEvents.value.map((item) => ({
      id: item.id,
      slug: item.slug,
      type: item.type,
      image: item.image_url || '/logo.png',
      date: (item.starts_at || item.published_at || '').slice(0, 10),
      time: item.starts_at ? item.starts_at.slice(11, 16) : '',
      title: { ar: item.title_ar, fr: item.title_fr },
      summary: { ar: item.description_ar, fr: item.description_fr },
      place: { ar: item.location_ar || item.location, fr: item.location_fr || item.location },
      organizer: { ar: item.department?.name_ar, fr: item.department?.name_fr },
    }))
  }
  return upcomingEvents
})

const eventSlides = computed(() =>
  imagesToSlides(
    events.value.map((e) => ({
      src: e.image,
      title: localized(e.title),
      caption: `${localized(e.title)} — ${e.date || ''}`,
      slug: e.slug,
    })),
  ),
)

onMounted(async () => {
  try {
    const data = await fetchPublicEvents({ per_page: 24 })
    apiEvents.value = data.data || []
  } catch {
    apiEvents.value = []
  } finally {
    loaded.value = true
  }
})
</script>

<template>
  <div>
    <PageHero :title="t('nav.events')" :subtitle="t('pages.events.subtitle')" />

    <section class="mx-auto max-w-6xl px-5 py-10 md:px-8">
      <h2 class="mb-5 text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('nav.gallery') }}</h2>
      <PhotoCarousel :slides="eventSlides" :interval="4500" />
    </section>

    <section class="mx-auto grid max-w-6xl gap-6 px-5 pb-12 md:grid-cols-3 md:px-8">
      <article v-for="event in events" :key="event.id" class="overflow-hidden rounded-xl bg-white shadow-sm">
        <img :src="event.image" alt="" class="h-48 w-full object-cover" loading="lazy" />
        <div class="space-y-2 p-5">
          <p v-if="event.type" class="text-xs font-semibold text-[var(--rdp-gold)]">
            {{ t(`secretariat.eventTypes.${event.type}`) }}
          </p>
          <h2 class="text-xl font-semibold">{{ localized(event.title) }}</h2>
          <p class="text-sm text-slate-600">
            {{ event.date }}
            <span v-if="event.time"> · {{ event.time }}</span>
            <span v-if="localized(event.place)"> · {{ localized(event.place) }}</span>
          </p>
          <p v-if="localized(event.organizer)" class="text-sm text-slate-500">{{ localized(event.organizer) }}</p>
          <p class="line-clamp-3 text-sm text-slate-700">{{ localized(event.summary) }}</p>
          <RouterLink :to="`/events/${event.slug}`" class="text-sm font-semibold text-[var(--rdp-forest)] hover:underline">
            {{ t('home.eventDetails') }}
          </RouterLink>
        </div>
      </article>
    </section>
  </div>
</template>
