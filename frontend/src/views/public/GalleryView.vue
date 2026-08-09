<script setup>
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PageHero from '@/components/public/PageHero.vue'
import { galleryAlbums } from '@/data/publicContent'

const { t, locale } = useI18n()
const localized = (item) => item?.[locale.value] || item?.fr || ''
</script>

<template>
  <div>
    <PageHero :title="t('nav.gallery')" :subtitle="t('pages.gallery.subtitle')" />
    <section class="mx-auto grid max-w-6xl gap-5 px-5 py-12 sm:grid-cols-2 lg:grid-cols-4 md:px-8">
      <RouterLink
        v-for="album in galleryAlbums"
        :key="album.slug"
        :to="`/gallery/${album.slug}`"
        class="overflow-hidden rounded-xl bg-white shadow-sm"
      >
        <img :src="album.cover" alt="" class="h-44 w-full object-cover" />
        <p class="p-4 text-sm font-medium text-[var(--rdp-forest)]">{{ localized(album.title) }}</p>
      </RouterLink>
    </section>
  </div>
</template>
