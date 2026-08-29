<script setup>
import { computed } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()
const auth = useAuthStore()

const menus = computed(() => [
  { to: '/admin/president', label: t('president.dash.menu.overview') },
  { to: '/secretariats', label: t('president.dash.menu.secretariats'), external: true },
  { to: '/admin/users', label: t('president.dash.menu.members') },
  { to: '/admin', label: t('president.dash.menu.academic') },
  { to: '/admin', label: t('president.dash.menu.stats') },
  { to: '/admin', label: t('president.dash.menu.reports') },
  { to: '/events', label: t('president.dash.menu.events'), external: true },
  { to: '/shura-council', label: t('president.dash.menu.shura'), external: true },
  { to: '/parents-council', label: t('president.dash.menu.parents'), external: true },
  { to: '/secretariats/external-relations', label: t('president.dash.menu.external'), external: true },
])
</script>

<template>
  <section class="space-y-8">
    <div>
      <h1 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('president.dash.title') }}</h1>
      <p class="mt-2 text-slate-600">
        {{ t('president.dash.welcome', { name: auth.fullName || t('org.president') }) }}
      </p>
      <p class="mt-1 text-sm text-slate-500">{{ t('president.dash.note') }}</p>
    </div>

    <div>
      <h2 class="text-lg font-semibold text-[var(--rdp-forest)]">{{ t('president.dash.menuTitle') }}</h2>
      <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
        <RouterLink
          v-for="item in menus"
          :key="item.label"
          :to="item.to"
          class="rounded-lg border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 hover:border-[var(--rdp-forest)]/40 hover:text-[var(--rdp-forest)]"
        >
          {{ item.label }}
        </RouterLink>
      </div>
    </div>

    <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-5 text-sm text-slate-600">
      <p>{{ t('president.dash.comingSoon') }}</p>
      <RouterLink to="/president" class="mt-3 inline-flex text-[var(--rdp-forest)] hover:underline">
        {{ t('president.dash.publicPage') }}
      </RouterLink>
    </div>
  </section>
</template>
