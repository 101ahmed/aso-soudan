<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PageHero from '@/components/public/PageHero.vue'
import { pickName, pickTitle } from '@/utils/localized'
import { fetchPublicSportsTeam } from '@/services/sports'

const route = useRoute()
const { t, locale } = useI18n()
const team = ref(null)
const error = ref('')

const title = computed(() => (team.value ? pickName(team.value, locale.value) : t('sportsAmanah.teamHub')))

onMounted(async () => {
  try {
    team.value = await fetchPublicSportsTeam(route.params.teamId)
  } catch (e) {
    error.value = e.response?.data?.message || t('sportsAmanah.publicError')
  }
})
</script>

<template>
  <div>
    <PageHero :title="title" :subtitle="team ? t(`sportsAmanah.ages.${team.age_category}`) : ''" />
    <div class="mx-auto max-w-6xl space-y-10 px-5 py-10 md:px-8">
      <p v-if="error" class="text-rose-700">{{ error }}</p>
      <template v-if="team">
        <p>
          <RouterLink to="/secretariats/sports" class="text-sm text-[var(--rdp-forest)] hover:underline">{{ t('sportsAmanah.backPublic') }}</RouterLink>
        </p>
        <section class="grid gap-4 md:grid-cols-3">
          <div class="rounded-xl bg-white p-4 shadow-sm">
            <p class="text-xs text-slate-500">{{ t('sportsAmanah.coach') }}</p>
            <p class="font-semibold">{{ locale === 'ar' ? team.coach_ar : team.coach_fr }}</p>
          </div>
          <div class="rounded-xl bg-white p-4 shadow-sm">
            <p class="text-xs text-slate-500">{{ t('sportsAmanah.manager') }}</p>
            <p class="font-semibold">{{ locale === 'ar' ? team.manager_ar : team.manager_fr }}</p>
          </div>
          <div class="rounded-xl bg-white p-4 shadow-sm">
            <p class="text-xs text-slate-500">{{ t('sportsAmanah.players') }}</p>
            <p class="font-semibold">{{ team.players_count || team.players?.length || 0 }}</p>
          </div>
        </section>
        <section v-if="team.ranking || team.points" class="rounded-xl bg-white p-5 shadow-sm">
          <h2 class="text-xl font-semibold text-[var(--rdp-forest)]">{{ t('sportsAmanah.stats') }}</h2>
          <p class="mt-2 text-sm text-slate-700">
            {{ t('sportsAmanah.ranking') }} {{ team.ranking || '—' }}
            · {{ t('sportsAmanah.points') }} {{ team.points }}
            · {{ team.wins }}/{{ team.draws }}/{{ team.losses }}
          </p>
        </section>
        <section>
          <h2 class="text-xl font-semibold text-[var(--rdp-forest)]">{{ t('sportsAmanah.tab.players') }}</h2>
          <div class="mt-4 grid gap-4 sm:grid-cols-2 md:grid-cols-3">
            <article v-for="item in team.players || []" :key="item.id" class="overflow-hidden rounded-xl bg-white shadow-sm">
              <img v-if="item.photo_url" :src="item.photo_url" alt="" class="h-40 w-full object-cover" />
              <div class="p-4">
                <p class="font-semibold">{{ pickName(item, locale) }} <span v-if="item.number">#{{ item.number }}</span></p>
                <p class="text-sm text-slate-600">{{ item.position }}</p>
                <p class="mt-1 text-xs text-slate-500">{{ t('sportsAmanah.goals') }} {{ item.goals }} · {{ t('sportsAmanah.apps') }} {{ item.appearances }}</p>
              </div>
            </article>
          </div>
        </section>
        <section v-if="team.staff?.length">
          <h2 class="text-xl font-semibold text-[var(--rdp-forest)]">{{ t('sportsAmanah.tab.staff') }}</h2>
          <div class="mt-4 grid gap-3 md:grid-cols-2">
            <article v-for="item in team.staff" :key="item.id" class="rounded-xl bg-white p-4 shadow-sm">
              <p class="font-semibold">{{ pickName(item, locale) }}</p>
              <p class="text-sm text-slate-600">{{ locale === 'ar' ? item.role_ar : item.role_fr }}</p>
            </article>
          </div>
        </section>
        <section v-if="team.matches?.length">
          <h2 class="text-xl font-semibold text-[var(--rdp-forest)]">{{ t('sportsAmanah.tab.matches') }}</h2>
          <ul class="mt-3 space-y-2">
            <li v-for="item in team.matches" :key="item.id" class="rounded-lg bg-white p-3 text-sm">
              {{ item.played_on }} · {{ locale === 'ar' ? item.opponent_ar : item.opponent_fr }}
              · {{ item.goals_for ?? '-' }}-{{ item.goals_against ?? '-' }}
            </li>
          </ul>
        </section>
        <section v-if="team.trainings?.length">
          <h2 class="text-xl font-semibold text-[var(--rdp-forest)]">{{ t('sportsAmanah.tab.trainings') }}</h2>
          <ul class="mt-3 space-y-2">
            <li v-for="item in team.trainings" :key="item.id" class="rounded-lg bg-white p-3 text-sm">
              {{ t(`sportsAmanah.weekdays.${item.weekday}`) }} · {{ item.starts_at }}-{{ item.ends_at }} · {{ item.location }}
            </li>
          </ul>
        </section>
        <section v-if="team.tournaments?.length">
          <h2 class="text-xl font-semibold text-[var(--rdp-forest)]">{{ t('sportsAmanah.tab.tournaments') }}</h2>
          <ul class="mt-3 space-y-2">
            <li v-for="item in team.tournaments" :key="item.id" class="rounded-lg bg-white p-3 text-sm">
              {{ pickTitle(item, locale) }} · {{ item.season }} · {{ item.ranking }}
            </li>
          </ul>
        </section>
      </template>
    </div>
  </div>
</template>
