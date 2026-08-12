<script setup>
import { computed } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()
const auth = useAuthStore()
const route = useRoute()

const allowed = computed(() =>
  auth.user?.roles?.some((r) =>
    [
      'SUPER_ADMIN',
      'PRESIDENT',
      'SHURA_COUNCIL',
      'SHURA_PRESIDENT',
      'SHURA_VICE_PRESIDENT',
      'SHURA_SECRETARY',
      'SHURA_MEMBER',
      'SHURA_CONTENT_EDITOR',
    ].includes(r.code),
  ) || auth.hasPermission('shura.member.view') || auth.hasPermission('news.view'),
)

const links = computed(() => [
  { to: '/admin/shura', label: t('shuraAdmin.overview'), exact: true },
  { to: '/admin/shura/members', label: t('shuraAdmin.members'), show: auth.hasPermission('shura.member.view') },
  { to: '/admin/shura/meetings', label: t('shuraAdmin.meetings'), show: auth.hasPermission('shura.meeting.view') },
  { to: '/admin/secretariats/shura/news', label: t('shuraAdmin.news'), show: auth.hasPermission('news.view') },
  { to: '/admin/secretariats/shura/announcements', label: t('shuraAdmin.announcements'), show: auth.hasPermission('announcement.view') },
  { to: '/admin/secretariats/shura/albums', label: t('shuraAdmin.gallery'), show: auth.hasPermission('gallery.view') },
].filter((l) => l.show !== false))

function isActive(link) {
  if (link.exact) return route.path === link.to
  return route.path.startsWith(link.to)
}
</script>

<template>
  <section v-if="!allowed" class="rounded-xl border border-rose-200 bg-rose-50 p-8 text-center">
    <p class="text-lg font-semibold text-rose-800">403</p>
    <p class="mt-2 text-sm text-rose-700">{{ t('shuraAdmin.forbidden') }}</p>
  </section>

  <section v-else class="space-y-6">
    <div>
      <p class="text-xs tracking-wide text-slate-500 uppercase">{{ t('shuraAdmin.badge') }}</p>
      <h1 class="mt-1 text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('org.shura') }}</h1>
      <p class="mt-1 text-sm text-slate-600">{{ t('shuraAdmin.note') }}</p>
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
