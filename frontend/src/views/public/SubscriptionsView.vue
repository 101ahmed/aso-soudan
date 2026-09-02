<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PageHero from '@/components/public/PageHero.vue'
import { fetchPublicFinanceDocument } from '@/services/finance'

const { t, locale } = useI18n()
const loading = ref(true)
const document = ref(null)

const title = computed(() => {
  const item = document.value
  if (!item) return t('financePublic.subscriptionsTitle')
  return locale.value === 'fr' ? (item.title_fr || item.title_ar) : (item.title_ar || item.title_fr)
})

const body = computed(() => {
  const item = document.value
  if (!item) return ''
  return locale.value === 'fr' ? (item.body_fr || item.body_ar || '') : (item.body_ar || item.body_fr || '')
})

onMounted(async () => {
  try {
    document.value = await fetchPublicFinanceDocument('subscriptions_announcement')
  } catch {
    document.value = null
  } finally {
    loading.value = false
  }
})
</script>

<template>
  <div>
    <PageHero :title="t('financePublic.subscriptionsTitle')" :subtitle="t('financePublic.subscriptionsSubtitle')" />
    <section class="mx-auto max-w-3xl space-y-5 px-5 py-12 md:px-8">
      <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>

      <article v-else-if="document" class="rounded-2xl bg-white p-6 shadow-sm">
        <p class="text-xs font-semibold tracking-wide text-[var(--rdp-forest)] uppercase">
          {{ t('financeAdmin.kindAnnouncement') }}
        </p>
        <h2 class="mt-2 text-2xl font-semibold text-[var(--rdp-forest)]">{{ title }}</h2>
        <p v-if="body" class="mt-4 whitespace-pre-line leading-relaxed text-slate-700">{{ body }}</p>
        <a
          v-if="document.file_url"
          :href="document.file_url"
          target="_blank"
          rel="noreferrer"
          class="mt-5 inline-flex rounded bg-[var(--rdp-forest)] px-4 py-2 text-sm font-semibold text-white"
        >
          {{ t('financePublic.viewDocument') }}
        </a>
      </article>

      <div v-else class="rounded-2xl bg-white p-6 text-slate-600 shadow-sm">
        <p>{{ t('financePublic.subscriptionsEmpty') }}</p>
      </div>

      <div class="flex flex-wrap gap-3 text-sm">
        <RouterLink to="/secretariats/finance" class="font-semibold text-[var(--rdp-forest)] hover:underline">
          {{ t('financePublic.backToFinance') }}
        </RouterLink>
        <RouterLink to="/register/member" class="font-semibold text-[var(--rdp-forest)] hover:underline">
          {{ t('financePublic.memberRegister') }}
        </RouterLink>
      </div>
    </section>
  </div>
</template>
