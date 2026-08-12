<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PageHero from '@/components/public/PageHero.vue'
import PhotoCarousel from '@/components/public/PhotoCarousel.vue'
import { galleryAlbums } from '@/data/publicContent'
import { fetchPublicAlbum } from '@/services/content'
import { albumsToSlides, imagesToSlides } from '@/utils/gallerySlides'

const route = useRoute()
const { t, locale } = useI18n()
const album = ref(null)
const loading = ref(true)
const notFound = ref(false)

const localized = (value) => {
  if (!value) return ''
  if (typeof value === 'string') return value
  return value?.[locale.value] || value?.fr || value?.ar || ''
}

const slides = computed(() => {
  if (!album.value) return []
  if (album.value.media?.length || album.value.cover_url || album.value.cover) {
    return albumsToSlides([album.value], locale.value)
  }
  if (album.value.cover) {
    return imagesToSlides(Array.from({ length: 6 }, () => album.value.cover), localized(album.value.title))
  }
  return []
})

async function load() {
  loading.value = true
  notFound.value = false
  album.value = null
  try {
    const item = await fetchPublicAlbum(route.params.slug)
    album.value = {
      title: { ar: item.title_ar, fr: item.title_fr },
      title_ar: item.title_ar,
      title_fr: item.title_fr,
      cover: item.cover_url,
      cover_url: item.cover_url,
      slug: item.slug,
      media: item.media || [],
    }
  } catch {
    const fallback = galleryAlbums.find((item) => item.slug === route.params.slug)
    if (fallback) {
      album.value = fallback
    } else {
      notFound.value = true
    }
  } finally {
    loading.value = false
  }
}

onMounted(load)
watch(() => route.params.slug, load)
</script>

<template>
  <div v-if="loading" class="mx-auto max-w-3xl px-5 py-20 text-sm text-slate-500">…</div>
  <div v-else-if="album">
    <PageHero :title="localized(album.title)" />
    <section class="mx-auto max-w-6xl px-5 py-12 md:px-8">
      <PhotoCarousel v-if="slides.length" :slides="slides" :interval="4500" />
      <p v-else class="text-sm text-slate-500">{{ t('pages.gallery.empty') }}</p>
    </section>
  </div>
  <div v-else class="mx-auto max-w-3xl px-5 py-20">{{ t('pages.notFound') }}</div>
</template>
