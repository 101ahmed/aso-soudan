<script setup>
import { computed } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'

const route = useRoute()
const { t } = useI18n()
const auth = useAuthStore()
const code = computed(() => route.params.code)

const cards = computed(() => {
  const items = [
    {
      to: `/admin/secretariats/${code.value}/messages`,
      label: t('secretariatAdmin.messages'),
      hint: t('secretariatAdmin.messagesHint'),
    },
    {
      to: `/admin/secretariats/${code.value}/officer`,
      label: t('secretariatAdmin.officer'),
      hint: t('secretariatAdmin.officerHint'),
    },
    {
      to: `/admin/secretariats/${code.value}/deputy`,
      label: t('secretariatAdmin.deputy'),
      hint: t('secretariatAdmin.deputyHint'),
    },
    { to: `/admin/secretariats/${code.value}/news`, label: t('secretariatAdmin.news'), hint: t('secretariatAdmin.newsHint') },
    {
      to: `/admin/secretariats/${code.value}/events`,
      label: t('secretariatAdmin.events'),
      hint: t('secretariatAdmin.eventsHint'),
    },
    { to: `/admin/secretariats/${code.value}/announcements`, label: t('secretariatAdmin.announcements'), hint: t('secretariatAdmin.announcementsHint') },
    { to: `/admin/secretariats/${code.value}/albums`, label: t('secretariatAdmin.albums'), hint: t('secretariatAdmin.albumsHint') },
  ]
  if (auth.hasPermission('report.view')) {
    items.push({
      to: `/admin/secretariats/${code.value}/reports`,
      label: t('secretariatAdmin.reports'),
      hint: t('secretariatAdmin.reportsHint'),
    })
  }
  if (code.value === 'academic') {
    items.unshift(
      {
        to: `/admin/secretariats/academic/teachers`,
        label: t('secretariatAdmin.teachers'),
        hint: t('secretariatAdmin.teachersHint'),
      },
      {
        to: `/admin/secretariats/academic/students`,
        label: t('secretariatAdmin.students'),
        hint: t('secretariatAdmin.studentsHint'),
      },
      {
        to: `/admin/secretariats/academic/attendance`,
        label: t('secretariatAdmin.attendance'),
        hint: t('secretariatAdmin.attendanceHint'),
      },
      {
        to: `/admin/secretariats/academic/timetable`,
        label: t('secretariatAdmin.timetable'),
        hint: t('secretariatAdmin.timetableHint'),
      },
    )
  }
  if (code.value === 'statistics') {
    items.unshift({
      to: `/admin/secretariats/statistics/members`,
      label: t('secretariatAdmin.members'),
      hint: t('secretariatAdmin.membersHint'),
    })
  }
  if (code.value === 'finance') {
    items.unshift(
      {
        to: `/admin/secretariats/finance/accounts`,
        label: t('secretariatAdmin.financeOverview'),
        hint: t('secretariatAdmin.financeOverviewHint'),
      },
      {
        to: `/admin/secretariats/finance/revenues`,
        label: t('secretariatAdmin.financeRevenues'),
        hint: t('secretariatAdmin.financeRevenuesHint'),
      },
      {
        to: `/admin/secretariats/finance/expenses`,
        label: t('secretariatAdmin.financeExpenses'),
        hint: t('secretariatAdmin.financeExpensesHint'),
      },
      {
        to: `/admin/secretariats/finance/documents#general_report`,
        label: t('secretariatAdmin.financeGeneralReport'),
        hint: t('secretariatAdmin.financeGeneralReportHint'),
      },
      {
        to: `/admin/secretariats/finance/documents#subscriptions_announcement`,
        label: t('secretariatAdmin.financeSubscriptions'),
        hint: t('secretariatAdmin.financeSubscriptionsHint'),
      },
    )
  }
  if (code.value === 'general') {
    items.unshift({
      to: `/admin/secretariats/general/decisions`,
      label: t('secretariatAdmin.executiveDecisions'),
      hint: t('secretariatAdmin.executiveDecisionsHint'),
    })
  }
  if (code.value === 'media') {
    items.unshift(
      {
        to: `/admin/secretariats/media/media-center`,
        label: t('secretariatAdmin.mediaCenter'),
        hint: t('secretariatAdmin.mediaCenterHint'),
      },
      {
        to: `/admin/secretariats/media/decisions`,
        label: t('secretariatAdmin.decisions'),
        hint: t('secretariatAdmin.decisionsHint'),
      },
    )
  }
  if (code.value === 'social') {
    items.unshift({
      to: `/admin/secretariats/social/help-requests`,
      label: t('secretariatAdmin.helpRequests'),
      hint: t('secretariatAdmin.helpRequestsHint'),
    })
  }
  if (code.value === 'external-relations') {
    items.unshift(
      {
        to: `/admin/secretariats/external-relations/partners`,
        label: t('secretariatAdmin.partners'),
        hint: t('secretariatAdmin.partnersHint'),
      },
      {
        to: `/admin/secretariats/external-relations/files`,
        label: t('secretariatAdmin.externalFiles'),
        hint: t('secretariatAdmin.externalFilesHint'),
      },
      {
        to: `/admin/secretariats/external-relations/contact-requests`,
        label: t('secretariatAdmin.contactRequests'),
        hint: t('secretariatAdmin.contactRequestsHint'),
      },
    )
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
