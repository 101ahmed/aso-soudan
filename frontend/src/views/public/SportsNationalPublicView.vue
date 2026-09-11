<script setup>
import { onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PageHero from '@/components/public/PageHero.vue'
import { pickName, pickTitle } from '@/utils/localized'
import { fetchPublicNationalTeam } from '@/services/sports'

const { t, locale } = useI18n()
const data = ref({ players: [], staff: [], matches: [], tournaments: [], camps: [], scorers: [] })
const error = ref('')

onMounted(async () => {
  try {
    data.value = await fetchPublicNationalTeam()
  } catch (e) {
    error.value = e.response?.data?.message || t('sportsAmanah.publicError')
  }
})
</script>

<template>
  <div>
    <PageHero :title="t('sportsAmanah.nationalPublicTitle')" :subtitle="t('sportsAmanah.nationalPublicSubtitle')" />
    <div class="mx-auto max-w-6xl space-y-10 px-5 py-10 md:px-8">
      <p>
        <RouterLink to="/secretariats/sports" class="text-sm text-[var(--rdp-forest)] hover:underline">{{ t('sportsAmanah.backPublic') }}</RouterLink>
      </p>
      <p v-if="error" class="text-rose-700">{{ error }}</p>

      <section>
        <h2 class="text-xl font-semibold text-[var(--rdp-forest)]">{{ t('sportsAmanah.tab.players') }}</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 md:grid-cols-3">
          <article v-for="item in data.players" :key="item.id" class="overflow-hidden rounded-xl bg-white shadow-sm">
            <img v-if="item.photo_url" :src="item.photo_url" alt="" class="h-40 w-full object-cover" />
            <div class="p-4">
              <p class="font-semibold">{{ pickName(item, locale) }}</p>
              <p class="text-sm text-slate-600">{{ item.position }}</p>
              <p class="text-xs text-slate-500">{{ t('sportsAmanah.goals') }} {{ item.goals }} · {{ t('sportsAmanah.apps') }} {{ item.appearances }}</p>
            </div>
          </article>
        </div>
      </section>

      <section v-if="data.scorers?.length">
        <h2 class="text-xl font-semibold text-[var(--rdp-forest)]">{{ t('sportsAmanah.scorers') }}</h2>
        <ol class="mt-3 space-y-2">
          <li v-for="item in data.scorers" :key="'s'+item.id" class="rounded-lg bg-white p-3 text-sm">
            {{ pickName(item, locale) }} — {{ item.goals }}
          </li>
        </ol>
      </section>

      <section v-if="data.staff?.length">
        <h2 class="text-xl font-semibold text-[var(--rdp-forest)]">{{ t('sportsAmanah.tab.staff') }}</h2>
        <div class="mt-4 grid gap-3 md:grid-cols-2">
          <article v-for="item in data.staff" :key="item.id" class="rounded-xl bg-white p-4 shadow-sm">
            <p class="font-semibold">{{ pickName(item, locale) }}</p>
            <p class="text-sm text-slate-600">{{ t(`sportsAmanah.staffKinds.${item.kind}`) }} · {{ locale === 'ar' ? item.role_ar : item.role_fr }}</p>
          </article>
        </div>
      </section>

      <section v-if="data.camps?.length">
        <h2 class="text-xl font-semibold text-[var(--rdp-forest)]">{{ t('sportsAmanah.tab.camps') }}</h2>
        <ul class="mt-3 space-y-2">
          <li v-for="item in data.camps" :key="item.id" class="rounded-lg bg-white p-3 text-sm">
            {{ pickTitle(item, locale) }} · {{ item.starts_on }} → {{ item.ends_on }} · {{ item.location }}
          </li>
        </ul>
      </section>

      <section v-if="data.matches?.length">
        <h2 class="text-xl font-semibold text-[var(--rdp-forest)]">{{ t('sportsAmanah.tab.matches') }}</h2>
        <ul class="mt-3 space-y-2">
          <li v-for="item in data.matches" :key="item.id" class="rounded-lg bg-white p-3 text-sm">
            {{ item.played_on }} · {{ locale === 'ar' ? item.opponent_ar : item.opponent_fr }} · {{ item.goals_for ?? '-' }}-{{ item.goals_against ?? '-' }}
          </li>
        </ul>
      </section>

      <section v-if="data.tournaments?.length">
        <h2 class="text-xl font-semibold text-[var(--rdp-forest)]">{{ t('sportsAmanah.tab.tournaments') }}</h2>
        <ul class="mt-3 space-y-2">
          <li v-for="item in data.tournaments" :key="item.id" class="rounded-lg bg-white p-3 text-sm">
            {{ pickTitle(item, locale) }} · {{ item.season }}
          </li>
        </ul>
      </section>
    </div>
  </div>
</template>
