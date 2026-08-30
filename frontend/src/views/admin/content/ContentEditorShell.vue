<script setup>
import { computed } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()
const auth = useAuthStore()
const route = useRoute()

const links = computed(() => {
  const items = [{ to: '/admin/content', label: t('contentAdmin.home'), exact: true }]
  if (auth.hasPermission('news.view')) {
    items.push({ to: '/admin/content/news', label: t('contentAdmin.news') })
  }
  if (auth.hasPermission('announcement.view')) {
    items.push({ to: '/admin/content/announcements', label: t('contentAdmin.announcements') })
  }
  return items
})

function isActive(link) {
  if (link.exact) return route.path === link.to
  return route.path.startsWith(link.to)
}
</script>

<template>
  <section class="space-y-6">
    <div>
      <p class="text-xs tracking-wide text-slate-500 uppercase">{{ t('contentAdmin.badge') }}</p>
      <h1 class="mt-1 text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('contentAdmin.title') }}</h1>
      <p class="mt-2 max-w-2xl text-sm text-slate-600">{{ t('contentAdmin.intro') }}</p>
    </div>

    <nav class="flex flex-wrap gap-2 border-b border-slate-200 pb-3">
      <RouterLink
        v-for="link in links"
        :key="link.to"
        :to="link.to"
        class="rounded-md px-3 py-1.5 text-sm"
        :class="isActive(link) ? 'bg-teal-800 text-white' : 'bg-white text-slate-700 hover:bg-slate-100'"
      >
        {{ link.label }}
      </RouterLink>
    </nav>

    <RouterView />
  </section>
</template>
