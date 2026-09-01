<script setup>
import { computed, inject, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { fetchPresidentOverview } from '@/services/president'

const { t } = useI18n()
const openDirective = inject('openPresidentDirective', () => {})
const directiveTick = inject('presidentDirectiveTick', ref(0))

const loading = ref(false)
const error = ref('')
const stats = ref({
  upcoming_meetings: 0,
  follow_up_meetings: 0,
  urgent_meetings: 0,
  sent_directives: 0,
  unread_directives: 0,
  archive_count: 0,
})

const cards = computed(() => [
  { to: '/admin/president/meetings?scope=upcoming', label: t('presidentAdmin.upcoming'), value: stats.value.upcoming_meetings, hint: t('presidentAdmin.upcomingHint') },
  { to: '/admin/president/meetings?classification=urgent', label: t('presidentAdmin.classifications.urgent'), value: stats.value.urgent_meetings, hint: t('presidentAdmin.urgentHint') },
  { to: '/admin/president/meetings?classification=follow_up', label: t('presidentAdmin.classifications.follow_up'), value: stats.value.follow_up_meetings, hint: t('presidentAdmin.followUpHint') },
  { to: '/admin/president/archive', label: t('presidentAdmin.archive'), value: stats.value.archive_count, hint: t('presidentAdmin.archiveHint') },
])

async function load() {
  loading.value = true
  error.value = ''
  try {
    stats.value = await fetchPresidentOverview()
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

watch(directiveTick, load)
onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <p class="text-sm text-slate-600">{{ t('presidentAdmin.homeHint') }}</p>
    <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
    <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
      <RouterLink
        v-for="card in cards"
        :key="card.label"
        :to="card.to"
        class="rounded-xl border bg-white p-5 hover:border-teal-700"
      >
        <p class="text-sm text-slate-500">{{ card.label }}</p>
        <p class="mt-2 text-3xl font-semibold text-[var(--rdp-forest)]">{{ card.value }}</p>
        <p class="mt-2 text-sm text-slate-600">{{ card.hint }}</p>
      </RouterLink>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
      <article class="rounded-xl border bg-white p-5">
        <h2 class="font-semibold text-[var(--rdp-forest)]">{{ t('presidentAdmin.meetings') }} 🗓️</h2>
        <p class="mt-2 text-sm text-slate-600">{{ t('presidentAdmin.meetingsHint') }}</p>
        <RouterLink to="/admin/president/meetings" class="mt-3 inline-flex text-sm font-semibold text-teal-800 hover:underline">
          {{ t('presidentAdmin.openMeetings') }}
        </RouterLink>
      </article>
      <article class="rounded-xl border bg-white p-5">
        <h2 class="font-semibold text-[var(--rdp-forest)]">{{ t('presidentAdmin.sendDirective') }}</h2>
        <p class="mt-2 text-sm text-slate-600">{{ t('presidentAdmin.directiveHomeHint') }}</p>
        <p class="mt-2 text-sm text-slate-500">
          {{ t('presidentAdmin.unreadDirectives', { count: stats.unread_directives }) }}
          · {{ t('presidentAdmin.sentDirectives', { count: stats.sent_directives }) }}
        </p>
        <button type="button" class="mt-3 rounded bg-teal-800 px-4 py-2 text-sm text-white" @click="openDirective">
          {{ t('presidentAdmin.sendDirective') }}
        </button>
      </article>
    </div>
  </div>
</template>
