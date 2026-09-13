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
  const items = []
  if (auth.hasPermission('inbox.view')) {
    items.push({
      to: `/admin/secretariats/${code.value}/inbox`,
      label: t('secretariatAdmin.messages'),
      hint: t('secretariatAdmin.messagesHint'),
    })
  }
  if (
    auth.hasPermission('secretariat.directive.view')
    || auth.hasPermission('inbox.view')
    || auth.hasPermission('secretariat.directive.send')
    || auth.hasPermission('inbox.create')
  ) {
    items.push({
      to: `/admin/secretariats/${code.value}/directives`,
      label: t('secretariatAdmin.directives'),
      hint: t('secretariatAdmin.directivesHint'),
    })
  }
  items.push(
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
  )
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
        to: `/admin/secretariats/academic/class-counselor`,
        label: t('secretariatAdmin.classCounselor'),
        hint: t('secretariatAdmin.classCounselorHint'),
      },
      {
        to: `/admin/secretariats/academic/class-supervisor`,
        label: t('secretariatAdmin.classSupervisor'),
        hint: t('secretariatAdmin.classSupervisorHint'),
      },
      {
        to: `/admin/secretariats/academic/students`,
        label: t('secretariatAdmin.students'),
        hint: t('secretariatAdmin.studentsHint'),
      },
      {
        to: `/admin/secretariats/academic/student-file`,
        label: t('secretariatAdmin.studentFile'),
        hint: t('secretariatAdmin.studentFileHint'),
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
      {
        to: `/admin/secretariats/finance/members`,
        label: t('secretariatAdmin.financeMembers'),
        hint: t('secretariatAdmin.financeMembersHint'),
      },
    )
  }
  if (code.value === 'general') {
    items.unshift(
      {
        to: `/admin/secretariats/general/decisions`,
        label: t('secretariatAdmin.executiveDecisions'),
        hint: t('secretariatAdmin.executiveDecisionsHint'),
      },
      {
        to: `/admin/secretariats/general/meeting-outputs`,
        label: t('secretariatAdmin.meetingOutputs'),
        hint: t('secretariatAdmin.meetingOutputsHint'),
      },
    )
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
    items.unshift(
      {
        to: `/admin/secretariats/social/help-requests`,
        label: t('secretariatAdmin.helpRequests'),
        hint: t('secretariatAdmin.helpRequestsHint'),
      },
      {
        to: `/admin/secretariats/social/visits`,
        label: t('secretariatAdmin.socialVisits'),
        hint: t('secretariatAdmin.socialVisitsHint'),
      },
    )
  }
  if (code.value === 'women-children' && auth.hasPermission('women_member.view')) {
    items.unshift(
      {
        to: `/admin/secretariats/women-children/women-members`,
        label: t('secretariatAdmin.womenMembers'),
        hint: t('secretariatAdmin.womenMembersHint'),
      },
    )
  }
  if (code.value === 'sports' && auth.hasPermission('sport.view')) {
    items.unshift(
      {
        to: `/admin/secretariats/sports/teams`,
        label: t('secretariatAdmin.sportsTeams'),
        hint: t('secretariatAdmin.sportsTeamsHint'),
      },
      {
        to: `/admin/secretariats/sports/national-team`,
        label: t('secretariatAdmin.sportsNational'),
        hint: t('secretariatAdmin.sportsNationalHint'),
      },
      {
        to: `/admin/secretariats/sports/sports-join-requests`,
        label: t('secretariatAdmin.sportsJoin'),
        hint: t('secretariatAdmin.sportsJoinHint'),
      },
    )
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
