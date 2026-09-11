<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { pickName, pickTitle } from '@/utils/localized'
import {
  MATCH_STATUSES,
  STAFF_KINDS,
  createSportsCamp,
  createSportsMatch,
  createSportsPlayer,
  createSportsStaff,
  createSportsTournament,
  deleteSportsCamp,
  deleteSportsMatch,
  deleteSportsPlayer,
  deleteSportsStaff,
  deleteSportsTournament,
  fetchSportsCamps,
  fetchSportsMatches,
  fetchSportsPlayers,
  fetchSportsStaff,
  fetchSportsTournaments,
} from '@/services/sports'

const route = useRoute()
const { t, locale } = useI18n()
const auth = useAuthStore()
const code = computed(() => route.params.code)
const tab = ref('players')
const error = ref('')
const players = ref([])
const staff = ref([])
const matches = ref([])
const tournaments = ref([])
const camps = ref([])

const canCreate = computed(() => auth.hasPermission('sport.create'))
const canDelete = computed(() => auth.hasPermission('sport.delete'))

const playerForm = reactive({ name_ar: '', name_fr: '', position: '', number: '', goals: 0, appearances: 0, assists: 0, is_public: true })
const staffForm = reactive({ name_ar: '', name_fr: '', role_ar: '', role_fr: '', kind: 'technical', is_public: true })
const matchForm = reactive({ opponent_ar: '', opponent_fr: '', competition_ar: '', competition_fr: '', played_on: '', venue: '', is_home: true, goals_for: '', goals_against: '', status: 'played', is_public: true })
const tournamentForm = reactive({ title_ar: '', title_fr: '', season: '', location: '', ranking: '', is_public: true })
const campForm = reactive({ title_ar: '', title_fr: '', starts_on: '', ends_on: '', location: '', notes_ar: '', notes_fr: '', is_public: true })
const playerPhoto = ref(null)
const staffPhoto = ref(null)

function err(e) {
  const errors = e.response?.data?.errors
  error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || e.message
}

async function load() {
  error.value = ''
  try {
    const [p, s, m, to, c] = await Promise.all([
      fetchSportsPlayers(code.value, { national: 1 }),
      fetchSportsStaff(code.value, { national: 1 }),
      fetchSportsMatches(code.value, { national: 1 }),
      fetchSportsTournaments(code.value, { national: 1 }),
      fetchSportsCamps(code.value),
    ])
    players.value = p.data || []
    staff.value = s.data || []
    matches.value = m.data || []
    tournaments.value = to.data || []
    camps.value = c.data || []
  } catch (e) { err(e) }
}

async function savePlayer() {
  if (!canCreate.value) return
  try {
    await createSportsPlayer(code.value, { ...playerForm, is_national: true, photo: playerPhoto.value || undefined })
    Object.assign(playerForm, { name_ar: '', name_fr: '', position: '', number: '', goals: 0, appearances: 0, assists: 0, is_public: true })
    playerPhoto.value = null
    await load()
  } catch (e) { err(e) }
}

async function saveStaff() {
  try {
    await createSportsStaff(code.value, { ...staffForm, is_national: true, photo: staffPhoto.value || undefined })
    Object.assign(staffForm, { name_ar: '', name_fr: '', role_ar: '', role_fr: '', kind: 'technical', is_public: true })
    staffPhoto.value = null
    await load()
  } catch (e) { err(e) }
}

async function saveMatch() {
  try {
    await createSportsMatch(code.value, { ...matchForm, is_national: true })
    Object.assign(matchForm, { opponent_ar: '', opponent_fr: '', competition_ar: '', competition_fr: '', played_on: '', venue: '', is_home: true, goals_for: '', goals_against: '', status: 'played', is_public: true })
    await load()
  } catch (e) { err(e) }
}

async function saveTournament() {
  try {
    await createSportsTournament(code.value, { ...tournamentForm, is_national: true })
    Object.assign(tournamentForm, { title_ar: '', title_fr: '', season: '', location: '', ranking: '', is_public: true })
    await load()
  } catch (e) { err(e) }
}

async function saveCamp() {
  try {
    await createSportsCamp(code.value, campForm)
    Object.assign(campForm, { title_ar: '', title_fr: '', starts_on: '', ends_on: '', location: '', notes_ar: '', notes_fr: '', is_public: true })
    await load()
  } catch (e) { err(e) }
}

async function remove(kind, id) {
  if (!canDelete.value) return
  if (!confirm(t('secretariatAdmin.confirmDelete'))) return
  if (kind === 'player') await deleteSportsPlayer(code.value, id)
  if (kind === 'staff') await deleteSportsStaff(code.value, id)
  if (kind === 'match') await deleteSportsMatch(code.value, id)
  if (kind === 'tournament') await deleteSportsTournament(code.value, id)
  if (kind === 'camp') await deleteSportsCamp(code.value, id)
  await load()
}

onMounted(load)
</script>

<template>
  <div class="space-y-4">
    <h2 class="text-lg font-semibold">{{ t('sportsAmanah.nationalTitle') }}</h2>
    <p class="text-sm text-slate-600">{{ t('sportsAmanah.nationalHint') }}</p>
    <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>
    <nav class="flex flex-wrap gap-2">
      <button v-for="key in ['players', 'staff', 'matches', 'tournaments', 'camps']" :key="key" type="button" class="rounded px-3 py-1.5 text-sm" :class="tab === key ? 'bg-teal-800 text-white' : 'border bg-white'" @click="tab = key">
        {{ t(`sportsAmanah.tab.${key}`) }}
      </button>
    </nav>

    <div v-if="tab === 'players'" class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
      <div class="space-y-2">
        <article v-for="item in players" :key="item.id" class="flex gap-3 rounded border bg-white p-3">
          <img v-if="item.photo_url" :src="item.photo_url" alt="" class="h-14 w-14 rounded object-cover" />
          <div class="flex-1">
            <p class="font-medium">{{ pickName(item, locale) }}</p>
            <p class="text-xs text-slate-600">{{ t('sportsAmanah.goals') }} {{ item.goals }} · {{ t('sportsAmanah.apps') }} {{ item.appearances }}</p>
          </div>
          <button v-if="canDelete" type="button" class="h-fit rounded border border-rose-300 px-2 py-1 text-xs text-rose-700" @click="remove('player', item.id)">{{ t('forms.delete') }}</button>
        </article>
      </div>
      <form class="space-y-2 rounded-xl border bg-white p-4" @submit.prevent="savePlayer">
        <h3 class="font-semibold">{{ t('sportsAmanah.newPlayer') }}</h3>
        <input v-model="playerForm.name_ar" required :placeholder="t('sportsAmanah.nameAr')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="playerForm.name_fr" required :placeholder="t('sportsAmanah.nameFr')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="playerForm.position" :placeholder="t('sportsAmanah.position')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="playerForm.number" type="number" :placeholder="t('sportsAmanah.number')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="playerForm.goals" type="number" min="0" :placeholder="t('sportsAmanah.goals')" class="w-full rounded border px-3 py-2 text-sm" />
        <input type="file" accept="image/*" class="w-full text-sm" @change="playerPhoto = $event.target.files?.[0] || null" />
        <label class="flex items-center gap-2 text-sm"><input v-model="playerForm.is_public" type="checkbox" /> {{ t('sportsAmanah.public') }}</label>
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white">{{ t('forms.save') }}</button>
      </form>
    </div>

    <div v-if="tab === 'staff'" class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
      <div class="space-y-2">
        <article v-for="item in staff" :key="item.id" class="rounded border bg-white p-3">
          <p class="font-medium">{{ pickName(item, locale) }}</p>
          <p class="text-xs text-slate-600">{{ t(`sportsAmanah.staffKinds.${item.kind}`) }} · {{ locale === 'ar' ? item.role_ar : item.role_fr }}</p>
          <button v-if="canDelete" type="button" class="mt-2 rounded border border-rose-300 px-2 py-1 text-xs text-rose-700" @click="remove('staff', item.id)">{{ t('forms.delete') }}</button>
        </article>
      </div>
      <form class="space-y-2 rounded-xl border bg-white p-4" @submit.prevent="saveStaff">
        <input v-model="staffForm.name_ar" required :placeholder="t('sportsAmanah.nameAr')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="staffForm.name_fr" required :placeholder="t('sportsAmanah.nameFr')" class="w-full rounded border px-3 py-2 text-sm" />
        <select v-model="staffForm.kind" class="w-full rounded border px-3 py-2 text-sm">
          <option v-for="kind in STAFF_KINDS" :key="kind" :value="kind">{{ t(`sportsAmanah.staffKinds.${kind}`) }}</option>
        </select>
        <input v-model="staffForm.role_ar" :placeholder="t('sportsAmanah.roleAr')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="staffForm.role_fr" :placeholder="t('sportsAmanah.roleFr')" class="w-full rounded border px-3 py-2 text-sm" />
        <input type="file" accept="image/*" class="w-full text-sm" @change="staffPhoto = $event.target.files?.[0] || null" />
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white">{{ t('forms.save') }}</button>
      </form>
    </div>

    <div v-if="tab === 'matches'" class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
      <div class="space-y-2">
        <article v-for="item in matches" :key="item.id" class="rounded border bg-white p-3">
          <p class="font-medium">{{ locale === 'ar' ? item.opponent_ar : item.opponent_fr }} · {{ item.goals_for ?? '-' }}-{{ item.goals_against ?? '-' }}</p>
          <button v-if="canDelete" type="button" class="mt-2 rounded border border-rose-300 px-2 py-1 text-xs text-rose-700" @click="remove('match', item.id)">{{ t('forms.delete') }}</button>
        </article>
      </div>
      <form class="space-y-2 rounded-xl border bg-white p-4" @submit.prevent="saveMatch">
        <input v-model="matchForm.opponent_ar" required :placeholder="t('sportsAmanah.opponentAr')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="matchForm.opponent_fr" required :placeholder="t('sportsAmanah.opponentFr')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="matchForm.played_on" type="date" class="w-full rounded border px-3 py-2 text-sm" />
        <div class="grid grid-cols-2 gap-2">
          <input v-model="matchForm.goals_for" type="number" :placeholder="t('sportsAmanah.goalsFor')" class="rounded border px-2 py-2 text-sm" />
          <input v-model="matchForm.goals_against" type="number" :placeholder="t('sportsAmanah.goalsAgainst')" class="rounded border px-2 py-2 text-sm" />
        </div>
        <select v-model="matchForm.status" class="w-full rounded border px-3 py-2 text-sm">
          <option v-for="st in MATCH_STATUSES" :key="st" :value="st">{{ t(`sportsAmanah.matchStatuses.${st}`) }}</option>
        </select>
        <label class="flex items-center gap-2 text-sm"><input v-model="matchForm.is_public" type="checkbox" /> {{ t('sportsAmanah.public') }}</label>
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white">{{ t('forms.save') }}</button>
      </form>
    </div>

    <div v-if="tab === 'tournaments'" class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
      <div class="space-y-2">
        <article v-for="item in tournaments" :key="item.id" class="rounded border bg-white p-3">
          <p class="font-medium">{{ pickTitle(item, locale) }}</p>
          <button v-if="canDelete" type="button" class="mt-2 rounded border border-rose-300 px-2 py-1 text-xs text-rose-700" @click="remove('tournament', item.id)">{{ t('forms.delete') }}</button>
        </article>
      </div>
      <form class="space-y-2 rounded-xl border bg-white p-4" @submit.prevent="saveTournament">
        <input v-model="tournamentForm.title_ar" required :placeholder="t('sportsAmanah.titleAr')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="tournamentForm.title_fr" required :placeholder="t('sportsAmanah.titleFr')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="tournamentForm.season" :placeholder="t('sportsAmanah.season')" class="w-full rounded border px-3 py-2 text-sm" />
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white">{{ t('forms.save') }}</button>
      </form>
    </div>

    <div v-if="tab === 'camps'" class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
      <div class="space-y-2">
        <article v-for="item in camps" :key="item.id" class="rounded border bg-white p-3">
          <p class="font-medium">{{ pickTitle(item, locale) }}</p>
          <p class="text-xs text-slate-600">{{ item.starts_on }} → {{ item.ends_on }} · {{ item.location }}</p>
          <button v-if="canDelete" type="button" class="mt-2 rounded border border-rose-300 px-2 py-1 text-xs text-rose-700" @click="remove('camp', item.id)">{{ t('forms.delete') }}</button>
        </article>
      </div>
      <form class="space-y-2 rounded-xl border bg-white p-4" @submit.prevent="saveCamp">
        <input v-model="campForm.title_ar" required :placeholder="t('sportsAmanah.titleAr')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="campForm.title_fr" required :placeholder="t('sportsAmanah.titleFr')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="campForm.starts_on" type="date" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="campForm.ends_on" type="date" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="campForm.location" :placeholder="t('sportsAmanah.venue')" class="w-full rounded border px-3 py-2 text-sm" />
        <label class="flex items-center gap-2 text-sm"><input v-model="campForm.is_public" type="checkbox" /> {{ t('sportsAmanah.public') }}</label>
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white">{{ t('forms.save') }}</button>
      </form>
    </div>
  </div>
</template>
