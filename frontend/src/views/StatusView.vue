<script setup>
import { onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { useAppStore } from '@/stores/app'

const { t } = useI18n()
const appStore = useAppStore()

onMounted(() => {
  appStore.checkHealth()
})
</script>

<template>
  <section class="mx-auto max-w-3xl px-6 py-16">
    <h1 class="mb-6 text-3xl font-semibold text-slate-900">
      {{ t('status.title') }}
    </h1>

    <div class="rounded-xl border border-slate-200 bg-white/80 p-6 shadow-sm backdrop-blur">
      <p v-if="appStore.loadingHealth" class="text-slate-600">
        {{ t('status.loading') }}
      </p>

      <div v-else-if="appStore.health" class="space-y-3">
        <p class="text-lg font-medium text-teal-800">
          {{ t('status.ok') }}
        </p>
        <p class="text-sm text-slate-600">{{ appStore.health.status }}</p>
      </div>

      <div v-else class="space-y-3">
        <p class="text-lg font-medium text-rose-700">
          {{ t('status.error') }}
        </p>
        <p class="text-sm text-slate-600">{{ appStore.healthError }}</p>
        <button
          type="button"
          class="rounded-md bg-slate-900 px-4 py-2 text-sm text-white"
          @click="appStore.checkHealth()"
        >
          {{ t('home.cta') }}
        </button>
      </div>
    </div>
  </section>
</template>
