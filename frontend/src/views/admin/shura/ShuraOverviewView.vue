<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { fetchShuraOverview } from '@/services/shura'

const { t } = useI18n()
const stats = ref(null)
const error = ref('')

onMounted(async () => {
  try {
    stats.value = await fetchShuraOverview()
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  }
})
</script>

<template>
  <div class="space-y-4">
    <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>
    <div v-if="stats" class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
      <article class="rounded-xl border bg-white p-4">
        <p class="text-xs text-slate-500">{{ t('shuraAdmin.kpiMembers') }}</p>
        <p class="mt-2 text-2xl font-semibold text-[var(--rdp-forest)]">{{ stats.members_count }}</p>
      </article>
      <article class="rounded-xl border bg-white p-4">
        <p class="text-xs text-slate-500">{{ t('shuraAdmin.kpiPublic') }}</p>
        <p class="mt-2 text-2xl font-semibold text-[var(--rdp-forest)]">{{ stats.public_members }}</p>
      </article>
      <article class="rounded-xl border bg-white p-4">
        <p class="text-xs text-slate-500">{{ t('shuraAdmin.kpiMeetings') }}</p>
        <p class="mt-2 text-2xl font-semibold text-[var(--rdp-forest)]">{{ stats.meetings_count }}</p>
      </article>
      <article class="rounded-xl border bg-white p-4">
        <p class="text-xs text-slate-500">{{ t('shuraAdmin.kpiUpcoming') }}</p>
        <p class="mt-2 text-2xl font-semibold text-[var(--rdp-forest)]">{{ stats.upcoming_meetings }}</p>
      </article>
    </div>
    <p class="text-sm text-slate-600">{{ t('shuraAdmin.overviewHint') }}</p>
    <RouterLink to="/shura-council" class="inline-flex text-sm text-[var(--rdp-forest)] hover:underline">
      {{ t('shuraAdmin.publicPage') }}
    </RouterLink>
  </div>
</template>
