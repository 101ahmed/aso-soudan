<script setup>
import { useI18n } from 'vue-i18n'
import PageHero from '@/components/public/PageHero.vue'

const { t } = useI18n()
</script>

<template>
  <div>
    <PageHero :title="t('about.privacy')" :subtitle="t('privacy.subtitle')" />
    <section class="mx-auto max-w-3xl space-y-4 px-5 py-12 text-slate-700 md:px-8">
      <p>{{ t('privacy.p1') }}</p>
      <p>{{ t('privacy.p2') }}</p>
      <p>{{ t('privacy.p3') }}</p>
      <p>{{ t('privacy.p4') }}</p>
    </section>
  </div>
</template>
