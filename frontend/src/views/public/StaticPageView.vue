<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import PageHero from '@/components/public/PageHero.vue'

const props = defineProps({
  titleKey: { type: String, required: true },
  bodyKey: { type: String, required: true },
  subtitleKey: { type: String, default: '' },
})

const { t } = useI18n()
const title = computed(() => t(props.titleKey))
const subtitle = computed(() => (props.subtitleKey ? t(props.subtitleKey) : ''))
const paragraphs = computed(() => t(props.bodyKey).split('\n').filter(Boolean))
</script>

<template>
  <div>
    <PageHero :title="title" :subtitle="subtitle" />
    <section class="mx-auto max-w-3xl space-y-4 px-5 py-12 md:px-8">
      <p v-for="(paragraph, index) in paragraphs" :key="index" class="leading-relaxed text-slate-700">
        {{ paragraph }}
      </p>
    </section>
  </div>
</template>
