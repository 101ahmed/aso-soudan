<script setup>
import { computed } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()
const auth = useAuthStore()
const route = useRoute()

const PARENTS_ROLES = [
  'PARENTS_COUNCIL',
  'PARENTS_PRESIDENT',
  'PARENTS_VICE_PRESIDENT',
  'PARENTS_TREASURER',
  'PARENTS_SECRETARY',
]

const allowed = computed(() =>
  auth.user?.roles?.some((r) => ['SUPER_ADMIN', 'PRESIDENT', ...PARENTS_ROLES].includes(r.code))
  || auth.hasPermission('parents.member.view')
  || auth.hasPermission('parents.registration.view')
  || auth.hasPermission('parents.meeting.view')
  || auth.hasPermission('parents.survey.view'),
)

const links = computed(() => [
  { to: '/admin/parents', label: t('parentsAdmin.home'), exact: true },
  {
    to: '/admin/parents/members',
    label: t('parentsAdmin.bureau'),
    show: auth.hasPermission('parents.member.view'),
  },
  {
    to: '/admin/parents/registrations',
    label: t('parentsAdmin.registrations'),
    show: auth.hasPermission('parents.registration.view'),
  },
  {
    to: '/admin/parents/meetings',
    label: t('parentsAdmin.meetings'),
    show: auth.hasPermission('parents.meeting.view'),
  },
  {
    to: '/admin/parents/surveys',
    label: t('parentsAdmin.surveys'),
    show: auth.hasPermission('parents.survey.view'),
  },
].filter((link) => link.show !== false))

function isActive(link) {
  if (link.exact) return route.path === link.to
  return route.path.startsWith(link.to)
}
</script>

<template>
  <section v-if="!allowed" class="rounded-xl border border-rose-200 bg-rose-50 p-8 text-center">
    <p class="text-lg font-semibold text-rose-800">403</p>
    <p class="mt-2 text-sm text-rose-700">{{ t('parentsAdmin.forbidden') }}</p>
  </section>

  <section v-else class="space-y-6">
    <div>
      <p class="text-xs tracking-wide text-slate-500 uppercase">{{ t('parentsAdmin.badge') }}</p>
      <h1 class="mt-1 text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('org.parents') }}</h1>
      <p class="mt-1 text-sm text-slate-600">{{ t('parentsAdmin.note') }}</p>
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
