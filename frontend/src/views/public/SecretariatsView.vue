<script setup>
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { secretariats } from '@/data/secretariats'

const { t, locale } = useI18n()
const localized = (value) => value?.[locale.value] || value?.fr || value?.ar || ''
</script>

<template>
  <div>
    <section class="relative overflow-hidden bg-[var(--rdp-forest)] px-5 py-16 text-white md:px-8">
      <div class="mx-auto max-w-6xl">
        <h1 class="font-[family-name:var(--font-display)] text-4xl font-bold md:text-5xl">
          {{ t('nav.secretariats') }}
        </h1>
        <p class="mt-4 max-w-3xl text-white/85">
          {{ t('pages.secretariats.subtitle') }}
        </p>
      </div>
    </section>

    <section class="mx-auto grid max-w-6xl gap-5 px-5 py-12 md:grid-cols-2 lg:grid-cols-3 md:px-8">
      <article
        v-for="item in secretariats"
        :key="item.slug"
        class="flex flex-col overflow-hidden rounded-2xl border border-[var(--rdp-forest)]/10 bg-white shadow-sm"
      >
        <img :src="item.banner" alt="" class="h-40 w-full object-cover" />
        <div class="flex flex-1 flex-col p-5">
          <h2 class="text-xl font-semibold text-[var(--rdp-forest)]">
            {{ t(item.nameKey) }}
          </h2>
          <p class="mt-2 flex-1 text-sm leading-relaxed text-slate-600">
            {{ localized(item.summary) }}
          </p>
          <RouterLink
            :to="`/secretariats/${item.slug}`"
            class="mt-4 inline-flex text-sm font-semibold text-[var(--rdp-forest)] hover:underline"
          >
            {{ t('pages.secretariats.learnMore') }}
          </RouterLink>
        </div>
      </article>
    </section>
  </div>
</template>
