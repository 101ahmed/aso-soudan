<script setup>
import { computed } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PageHero from '@/components/public/PageHero.vue'
import { galleryAlbums } from '@/data/publicContent'

const route = useRoute()
const { t, locale } = useI18n()
const album = computed(() => galleryAlbums.find((item) => item.slug === route.params.slug))
const localized = (value) => value?.[locale.value] || value?.fr || ''
</script>

<template>
  <div v-if="album">
    <PageHero :title="localized(album.title)" />
    <section class="mx-auto grid max-w-6xl gap-4 px-5 py-12 sm:grid-cols-2 md:grid-cols-3 md:px-8">
      <img
        v-for="n in 6"
        :key="n"
        :src="album.cover"
        alt=""
        class="h-52 w-full rounded-xl object-cover"
      />
    </section>
  </div>
  <div v-else class="mx-auto max-w-3xl px-5 py-20">{{ t('pages.notFound') }}</div>
</template>
