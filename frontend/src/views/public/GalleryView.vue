<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PageHero from '@/components/public/PageHero.vue'
import PhotoCarousel from '@/components/public/PhotoCarousel.vue'
import { galleryAlbums } from '@/data/publicContent'
import { fetchPublicAlbums } from '@/services/content'
import { albumsToSlides } from '@/utils/gallerySlides'

const { t, locale } = useI18n()
const albums = ref([])
const loading = ref(true)

const localized = (item) => item?.[locale.value] || item?.en || item?.fr || item?.ar || ''

const slides = computed(() => albumsToSlides(albums.value, locale.value))

onMounted(async () => {
  try {
    const data = await fetchPublicAlbums({ per_page: 24 })
    const list = data.data || []
    albums.value = list.length
      ? list.map((item) => ({
          id: item.id,
          slug: item.slug || String(item.id),
          cover: item.cover_url || item.media?.[0]?.url || '/logo.png',
          cover_url: item.cover_url,
          title: { ar: item.title_ar, fr: item.title_fr },
          title_ar: item.title_ar,
          title_fr: item.title_fr,
          media: item.media || [],
        }))
      : galleryAlbums
  } catch {
    albums.value = galleryAlbums
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <PageHero :title="t('nav.gallery')" :subtitle="t('pages.gallery.subtitle')" />

    <section class="mx-auto max-w-6xl space-y-10 px-5 py-12 md:px-8">
      <p v-if="loading" class="text-sm text-slate-500">…</p>

      <PhotoCarousel v-if="slides.length" :slides="slides" :interval="5000" />

      <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <RouterLink
          v-for="album in albums"
          :key="album.slug"
          :to="`/gallery/${album.slug}`"
          class="overflow-hidden rounded-xl bg-white shadow-sm"
        >
          <img :src="album.cover || album.cover_url" alt="" class="h-44 w-full object-cover" loading="lazy" />
          <p class="p-4 text-sm font-medium text-[var(--rdp-forest)]">{{ localized(album.title) }}</p>
        </RouterLink>
      </div>
    </section>
  </div>
</template>
