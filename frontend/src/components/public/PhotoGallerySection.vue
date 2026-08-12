<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PhotoCarousel from '@/components/public/PhotoCarousel.vue'
import { albumsToSlides, imagesToSlides } from '@/utils/gallerySlides'

const props = defineProps({
  title: { type: String, default: '' },
  albums: { type: Array, default: () => [] },
  images: { type: Array, default: () => [] },
  moreTo: { type: String, default: '/gallery' },
  showMore: { type: Boolean, default: true },
  interval: { type: Number, default: 5000 },
})

const { t, locale } = useI18n()

const slides = computed(() => {
  if (props.images?.length) return imagesToSlides(props.images)
  return albumsToSlides(props.albums, locale.value)
})

const heading = computed(() => props.title || t('nav.gallery'))
</script>

<template>
  <section v-if="slides.length" class="space-y-5">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <h2 class="text-2xl font-semibold text-[var(--rdp-forest)] md:text-3xl">{{ heading }}</h2>
      <RouterLink
        v-if="showMore && moreTo"
        :to="moreTo"
        class="text-sm font-semibold text-[var(--rdp-forest)] hover:underline"
      >
        {{ t('home.galleryCta') }}
      </RouterLink>
    </div>
    <PhotoCarousel :slides="slides" :interval="interval" />
  </section>
</template>
