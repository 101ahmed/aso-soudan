<script setup>
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()

const cards = [
  { to: '/admin/parents/registrations', label: 'parentsAdmin.registrations', hint: 'parentsAdmin.registrationsHint' },
  { to: '/admin/parents/meetings', label: 'parentsAdmin.meetings', hint: 'parentsAdmin.meetingsHint' },
  { to: '/admin/parents/surveys', label: 'parentsAdmin.surveys', hint: 'parentsAdmin.surveysHint' },
]
</script>

<template>
  <div class="space-y-4">
    <p class="text-sm text-slate-600">{{ t('parentsAdmin.homeHint') }}</p>
    <div class="grid gap-4 md:grid-cols-3">
      <RouterLink
        v-for="card in cards"
        :key="card.to"
        :to="card.to"
        class="rounded-xl border bg-white p-5 hover:border-teal-700"
      >
        <p class="font-semibold text-[var(--rdp-forest)]">{{ t(card.label) }}</p>
        <p class="mt-2 text-sm text-slate-600">{{ t(card.hint) }}</p>
      </RouterLink>
    </div>
    <RouterLink to="/parents-council" class="inline-flex text-sm font-semibold text-teal-800 hover:underline">
      {{ t('parentsAdmin.publicPage') }}
    </RouterLink>
  </div>
</template>
