<script setup>
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'

const route = useRoute()
const { t } = useI18n()
const code = computed(() => route.params.code)

const cards = computed(() => {
  const items = [
    {
      to: `/admin/secretariats/${code.value}/officer`,
      label: t('secretariatAdmin.officer'),
      hint: t('secretariatAdmin.officerHint'),
    },
    { to: `/admin/secretariats/${code.value}/news`, label: t('secretariatAdmin.news'), hint: t('secretariatAdmin.newsHint') },
    { to: `/admin/secretariats/${code.value}/announcements`, label: t('secretariatAdmin.announcements'), hint: t('secretariatAdmin.announcementsHint') },
    { to: `/admin/secretariats/${code.value}/albums`, label: t('secretariatAdmin.albums'), hint: t('secretariatAdmin.albumsHint') },
  ]
  if (code.value === 'academic') {
    items.unshift({
      to: `/admin/secretariats/academic/attendance`,
      label: t('secretariatAdmin.attendance'),
      hint: t('secretariatAdmin.attendanceHint'),
    })
  }
  return items
})
</script>

<template>
  <div class="space-y-4">
    <p class="text-sm text-slate-600">{{ t('secretariatAdmin.homeIntro') }}</p>
    <div class="grid gap-4 md:grid-cols-3">
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
    <p class="text-xs text-slate-500">{{ t('secretariatAdmin.comingSoon') }}</p>
  </div>
</template>
