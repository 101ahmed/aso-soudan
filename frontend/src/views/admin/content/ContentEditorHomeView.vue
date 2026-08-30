<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()
const auth = useAuthStore()

const cards = computed(() => {
  const items = []
  if (auth.hasPermission('news.view')) {
    items.push({
      to: '/admin/content/news',
      label: t('contentAdmin.news'),
      hint: t('contentAdmin.newsHint'),
    })
  }
  if (auth.hasPermission('announcement.view')) {
    items.push({
      to: '/admin/content/announcements',
      label: t('contentAdmin.announcements'),
      hint: t('contentAdmin.announcementsHint'),
    })
  }
  return items
})
</script>

<template>
  <div class="space-y-4">
    <p class="text-sm text-slate-600">{{ t('contentAdmin.homeIntro') }}</p>
    <div class="grid gap-4 md:grid-cols-2">
      <RouterLink
        v-for="card in cards"
        :key="card.to"
        :to="card.to"
        class="rounded-xl border border-slate-200 bg-white p-5 hover:border-teal-700/40"
      >
        <h2 class="font-semibold text-[var(--rdp-forest)]">{{ card.label }}</h2>
        <p class="mt-2 text-sm text-slate-600">{{ card.hint }}</p>
      </RouterLink>
    </div>
  </div>
</template>
