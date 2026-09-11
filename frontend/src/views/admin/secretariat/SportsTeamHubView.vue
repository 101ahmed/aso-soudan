<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { pickName } from '@/utils/localized'
import {
  MATCH_STATUSES,
  STAFF_KINDS,
  createSportsMatch,
  createSportsPlayer,
  createSportsStaff,
  createSportsTournament,
  createSportsTraining,
  deleteSportsMatch,
  deleteSportsPlayer,
  deleteSportsStaff,
  deleteSportsTournament,
  deleteSportsTraining,
  fetchSportsTeam,
  updateSportsMatch,
  updateSportsPlayer,
  updateSportsStaff,
  updateSportsTeam,
  updateSportsTournament,
  updateSportsTraining,
} from '@/services/sports'

const route = useRoute()
const { t, locale } = useI18n()
const auth = useAuthStore()
const code = computed(() => route.params.code)
const teamId = computed(() => Number(route.params.teamId))

const loading = ref(false)
const error = ref('')
const team = ref(null)
const tab = ref('players')

const canCreate = computed(() => auth.hasPermission('sport.create'))
const canUpdate = computed(() => auth.hasPermission('sport.update'))
const canDelete = computed(() => auth.hasPermission('sport.delete'))

const playerForm = reactive(emptyPlayer())
const staffForm = reactive(emptyStaff())
const matchForm = reactive(emptyMatch())
const trainingForm = reactive(emptyTraining())
const tournamentForm = reactive(emptyTournament())
const statsForm = reactive(emptyStats())
const playerPhoto = ref(null)
const staffPhoto = ref(null)
const editingPlayer = ref(null)
const editingStaff = ref(null)
const editingMatch = ref(null)
const editingTraining = ref(null)
const editingTournament = ref(null)

function emptyPlayer() {
  return { name_ar: '', name_fr: '', position: '', number: '', birth_date: '', appearances: 0, goals: 0, assists: 0, yellow_cards: 0, red_cards: 0, is_national: false, is_public: false }
}
function emptyStaff() {
  return { name_ar: '', name_fr: '', role_ar: '', role_fr: '', kind: 'technical', is_national: false, is_public: false }
}
function emptyMatch() {
  return { opponent_ar: '', opponent_fr: '', competition_ar: '', competition_fr: '', played_on: '', venue: '', is_home: true, goals_for: '', goals_against: '', status: 'played', is_public: false }
}
function emptyTraining() {
  return { weekday: 3, starts_at: '18:00', ends_at: '19:30', location: '', notes_ar: '', notes_fr: '', is_public: false }
}
function emptyTournament() {
  return { title_ar: '', title_fr: '', season: '', location: '', ranking: '', notes_ar: '', notes_fr: '', is_public: false }
}
function emptyStats() {
  return { ranking: '', points: '', played: '', wins: '', draws: '', losses: '', goals_for: '', goals_against: '' }
}

function fillStats(item) {
  Object.assign(statsForm, {
    ranking: item.ranking ?? '',
    points: item.points ?? 0,
    played: item.played ?? 0,
    wins: item.wins ?? 0,
    draws: item.draws ?? 0,
    losses: item.losses ?? 0,
    goals_for: item.goals_for ?? 0,
    goals_against: item.goals_against ?? 0,
  })
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    team.value = await fetchSportsTeam(code.value, teamId.value)
    fillStats(team.value)
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

function err(e) {
  const errors = e.response?.data?.errors
  error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || e.message
}

async function saveStats() {
  if (!canUpdate.value) return
  try {
    await updateSportsTeam(code.value, teamId.value, statsForm)
    await load()
  } catch (e) { err(e) }
}

async function savePlayer() {
  try {
    const payload = { ...playerForm, sports_team_id: teamId.value, photo: playerPhoto.value || undefined }
    if (editingPlayer.value) await updateSportsPlayer(code.value, editingPlayer.value, payload)
    else {
      if (!canCreate.value) return
      await createSportsPlayer(code.value, payload)
    }
    Object.assign(playerForm, emptyPlayer())
    editingPlayer.value = null
    playerPhoto.value = null
    await load()
  } catch (e) { err(e) }
}

function editPlayer(item) {
  editingPlayer.value = item.id
  playerPhoto.value = null
  Object.assign(playerForm, {
    name_ar: item.name_ar, name_fr: item.name_fr, position: item.position || '', number: item.number ?? '',
    birth_date: item.birth_date || '', appearances: item.appearances, goals: item.goals, assists: item.assists,
    yellow_cards: item.yellow_cards, red_cards: item.red_cards, is_national: !!item.is_national, is_public: !!item.is_public,
  })
}

async function saveStaff() {
  try {
    const payload = { ...staffForm, sports_team_id: teamId.value, photo: staffPhoto.value || undefined }
    if (editingStaff.value) await updateSportsStaff(code.value, editingStaff.value, payload)
    else await createSportsStaff(code.value, payload)
    Object.assign(staffForm, emptyStaff())
    editingStaff.value = null
    staffPhoto.value = null
    await load()
  } catch (e) { err(e) }
}

function editStaff(item) {
  editingStaff.value = item.id
  staffPhoto.value = null
  Object.assign(staffForm, {
    name_ar: item.name_ar, name_fr: item.name_fr, role_ar: item.role_ar || '', role_fr: item.role_fr || '',
    kind: item.kind || 'technical', is_national: !!item.is_national, is_public: !!item.is_public,
  })
}

async function saveMatch() {
  try {
    const payload = { ...matchForm, sports_team_id: teamId.value, is_national: false }
    if (editingMatch.value) await updateSportsMatch(code.value, editingMatch.value, payload)
    else await createSportsMatch(code.value, payload)
    Object.assign(matchForm, emptyMatch())
    editingMatch.value = null
    await load()
  } catch (e) { err(e) }
}

function editMatch(item) {
  editingMatch.value = item.id
  Object.assign(matchForm, {
    opponent_ar: item.opponent_ar, opponent_fr: item.opponent_fr, competition_ar: item.competition_ar || '',
    competition_fr: item.competition_fr || '', played_on: item.played_on || '', venue: item.venue || '',
    is_home: !!item.is_home, goals_for: item.goals_for ?? '', goals_against: item.goals_against ?? '',
    status: item.status, is_public: !!item.is_public,
  })
}

async function saveTraining() {
  try {
    const payload = { ...trainingForm, sports_team_id: teamId.value }
    if (editingTraining.value) await updateSportsTraining(code.value, editingTraining.value, payload)
    else await createSportsTraining(code.value, payload)
    Object.assign(trainingForm, emptyTraining())
    editingTraining.value = null
    await load()
  } catch (e) { err(e) }
}

function editTraining(item) {
  editingTraining.value = item.id
  Object.assign(trainingForm, {
    weekday: item.weekday, starts_at: item.starts_at || '', ends_at: item.ends_at || '',
    location: item.location || '', notes_ar: item.notes_ar || '', notes_fr: item.notes_fr || '', is_public: !!item.is_public,
  })
}

async function saveTournament() {
  try {
    const payload = { ...tournamentForm, sports_team_id: teamId.value, is_national: false }
    if (editingTournament.value) await updateSportsTournament(code.value, editingTournament.value, payload)
    else await createSportsTournament(code.value, payload)
    Object.assign(tournamentForm, emptyTournament())
    editingTournament.value = null
    await load()
  } catch (e) { err(e) }
}

function editTournament(item) {
  editingTournament.value = item.id
  Object.assign(tournamentForm, {
    title_ar: item.title_ar, title_fr: item.title_fr, season: item.season || '', location: item.location || '',
    ranking: item.ranking || '', notes_ar: item.notes_ar || '', notes_fr: item.notes_fr || '', is_public: !!item.is_public,
  })
}

async function remove(kind, id) {
  if (!canDelete.value) return
  if (!confirm(t('secretariatAdmin.confirmDelete'))) return
  if (kind === 'player') await deleteSportsPlayer(code.value, id)
  if (kind === 'staff') await deleteSportsStaff(code.value, id)
  if (kind === 'match') await deleteSportsMatch(code.value, id)
  if (kind === 'training') await deleteSportsTraining(code.value, id)
  if (kind === 'tournament') await deleteSportsTournament(code.value, id)
  await load()
}

onMounted(load)
</script>

<template>
  <div class="space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div>
        <RouterLink :to="`/admin/secretariats/${code}/teams`" class="text-sm text-teal-800 hover:underline">{{ t('sportsAmanah.backTeams') }}</RouterLink>
        <h2 class="text-lg font-semibold">{{ team ? pickName(team, locale) : t('sportsAmanah.teamHub') }}</h2>
        <p v-if="team" class="text-sm text-slate-600">{{ t(`sportsAmanah.ages.${team.age_category}`) }} · {{ team.players_count || 0 }} {{ t('sportsAmanah.players') }}</p>
      </div>
    </div>
    <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>
    <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>

    <form v-if="team" class="grid gap-2 rounded-xl border bg-white p-4 sm:grid-cols-4" @submit.prevent="saveStats">
      <p class="sm:col-span-4 font-medium">{{ t('sportsAmanah.stats') }}</p>
      <input v-model="statsForm.ranking" type="number" :placeholder="t('sportsAmanah.ranking')" class="rounded border px-2 py-2 text-sm" />
      <input v-model="statsForm.points" type="number" :placeholder="t('sportsAmanah.points')" class="rounded border px-2 py-2 text-sm" />
      <input v-model="statsForm.played" type="number" :placeholder="t('sportsAmanah.played')" class="rounded border px-2 py-2 text-sm" />
      <input v-model="statsForm.wins" type="number" :placeholder="t('sportsAmanah.wins')" class="rounded border px-2 py-2 text-sm" />
      <input v-model="statsForm.draws" type="number" :placeholder="t('sportsAmanah.draws')" class="rounded border px-2 py-2 text-sm" />
      <input v-model="statsForm.losses" type="number" :placeholder="t('sportsAmanah.losses')" class="rounded border px-2 py-2 text-sm" />
      <input v-model="statsForm.goals_for" type="number" :placeholder="t('sportsAmanah.goalsFor')" class="rounded border px-2 py-2 text-sm" />
      <input v-model="statsForm.goals_against" type="number" :placeholder="t('sportsAmanah.goalsAgainst')" class="rounded border px-2 py-2 text-sm" />
      <button type="submit" class="rounded bg-teal-800 px-3 py-2 text-sm text-white sm:col-span-4">{{ t('forms.save') }}</button>
    </form>

    <nav class="flex flex-wrap gap-2">
      <button v-for="key in ['players', 'staff', 'matches', 'trainings', 'tournaments']" :key="key" type="button" class="rounded px-3 py-1.5 text-sm" :class="tab === key ? 'bg-teal-800 text-white' : 'bg-white border'" @click="tab = key">
        {{ t(`sportsAmanah.tab.${key}`) }}
      </button>
    </nav>

    <div v-if="tab === 'players'" class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
      <div class="space-y-2">
        <article v-for="item in team?.players || []" :key="item.id" class="flex gap-3 rounded border bg-white p-3">
          <img v-if="item.photo_url" :src="item.photo_url" alt="" class="h-14 w-14 rounded object-cover" />
          <div class="flex-1">
            <p class="font-medium">{{ pickName(item, locale) }} <span v-if="item.number" class="text-slate-500">#{{ item.number }}</span></p>
            <p class="text-xs text-slate-600">{{ item.position }} · {{ t('sportsAmanah.goals') }} {{ item.goals }} · {{ t('sportsAmanah.apps') }} {{ item.appearances }}</p>
          </div>
          <div class="flex flex-col gap-1">
            <button type="button" class="rounded border px-2 py-1 text-xs" @click="editPlayer(item)">{{ t('forms.edit') }}</button>
            <button v-if="canDelete" type="button" class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700" @click="remove('player', item.id)">{{ t('forms.delete') }}</button>
          </div>
        </article>
      </div>
      <form class="space-y-2 rounded-xl border bg-white p-4" @submit.prevent="savePlayer">
        <h3 class="font-semibold">{{ editingPlayer ? t('sportsAmanah.editPlayer') : t('sportsAmanah.newPlayer') }}</h3>
        <input v-model="playerForm.name_ar" required :placeholder="t('sportsAmanah.nameAr')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="playerForm.name_fr" required :placeholder="t('sportsAmanah.nameFr')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="playerForm.position" :placeholder="t('sportsAmanah.position')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="playerForm.number" type="number" min="0" max="99" :placeholder="t('sportsAmanah.number')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="playerForm.birth_date" type="date" class="w-full rounded border px-3 py-2 text-sm" />
        <div class="grid grid-cols-2 gap-2">
          <input v-model="playerForm.appearances" type="number" min="0" :placeholder="t('sportsAmanah.apps')" class="rounded border px-2 py-2 text-sm" />
          <input v-model="playerForm.goals" type="number" min="0" :placeholder="t('sportsAmanah.goals')" class="rounded border px-2 py-2 text-sm" />
          <input v-model="playerForm.assists" type="number" min="0" :placeholder="t('sportsAmanah.assists')" class="rounded border px-2 py-2 text-sm" />
          <input v-model="playerForm.yellow_cards" type="number" min="0" :placeholder="t('sportsAmanah.yellow')" class="rounded border px-2 py-2 text-sm" />
        </div>
        <input type="file" accept="image/*" class="w-full text-sm" @change="playerPhoto = $event.target.files?.[0] || null" />
        <label class="flex items-center gap-2 text-sm"><input v-model="playerForm.is_national" type="checkbox" /> {{ t('sportsAmanah.nationalFlag') }}</label>
        <label class="flex items-center gap-2 text-sm"><input v-model="playerForm.is_public" type="checkbox" /> {{ t('sportsAmanah.public') }}</label>
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white">{{ t('forms.save') }}</button>
      </form>
    </div>

    <div v-if="tab === 'staff'" class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
      <div class="space-y-2">
        <article v-for="item in team?.staff || []" :key="item.id" class="flex gap-3 rounded border bg-white p-3">
          <img v-if="item.photo_url" :src="item.photo_url" alt="" class="h-14 w-14 rounded object-cover" />
          <div class="flex-1">
            <p class="font-medium">{{ pickName(item, locale) }}</p>
            <p class="text-xs text-slate-600">{{ t(`sportsAmanah.staffKinds.${item.kind}`) }} · {{ locale === 'ar' ? item.role_ar : item.role_fr }}</p>
          </div>
          <div class="flex flex-col gap-1">
            <button type="button" class="rounded border px-2 py-1 text-xs" @click="editStaff(item)">{{ t('forms.edit') }}</button>
            <button v-if="canDelete" type="button" class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700" @click="remove('staff', item.id)">{{ t('forms.delete') }}</button>
          </div>
        </article>
      </div>
      <form class="space-y-2 rounded-xl border bg-white p-4" @submit.prevent="saveStaff">
        <h3 class="font-semibold">{{ t('sportsAmanah.newStaff') }}</h3>
        <input v-model="staffForm.name_ar" required :placeholder="t('sportsAmanah.nameAr')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="staffForm.name_fr" required :placeholder="t('sportsAmanah.nameFr')" class="w-full rounded border px-3 py-2 text-sm" />
        <select v-model="staffForm.kind" class="w-full rounded border px-3 py-2 text-sm">
          <option v-for="kind in STAFF_KINDS" :key="kind" :value="kind">{{ t(`sportsAmanah.staffKinds.${kind}`) }}</option>
        </select>
        <input v-model="staffForm.role_ar" :placeholder="t('sportsAmanah.roleAr')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="staffForm.role_fr" :placeholder="t('sportsAmanah.roleFr')" class="w-full rounded border px-3 py-2 text-sm" />
        <input type="file" accept="image/*" class="w-full text-sm" @change="staffPhoto = $event.target.files?.[0] || null" />
        <label class="flex items-center gap-2 text-sm"><input v-model="staffForm.is_public" type="checkbox" /> {{ t('sportsAmanah.public') }}</label>
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white">{{ t('forms.save') }}</button>
      </form>
    </div>

    <div v-if="tab === 'matches'" class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
      <div class="space-y-2">
        <article v-for="item in team?.matches || []" :key="item.id" class="rounded border bg-white p-3">
          <p class="font-medium">{{ locale === 'ar' ? item.opponent_ar : item.opponent_fr }} · {{ item.goals_for ?? '-' }}-{{ item.goals_against ?? '-' }}</p>
          <p class="text-xs text-slate-600">{{ item.played_on }} · {{ t(`sportsAmanah.matchStatuses.${item.status}`) }}</p>
          <div class="mt-1 flex gap-1">
            <button type="button" class="rounded border px-2 py-1 text-xs" @click="editMatch(item)">{{ t('forms.edit') }}</button>
            <button v-if="canDelete" type="button" class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700" @click="remove('match', item.id)">{{ t('forms.delete') }}</button>
          </div>
        </article>
      </div>
      <form class="space-y-2 rounded-xl border bg-white p-4" @submit.prevent="saveMatch">
        <h3 class="font-semibold">{{ t('sportsAmanah.newMatch') }}</h3>
        <input v-model="matchForm.opponent_ar" required :placeholder="t('sportsAmanah.opponentAr')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="matchForm.opponent_fr" required :placeholder="t('sportsAmanah.opponentFr')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="matchForm.competition_ar" :placeholder="t('sportsAmanah.competitionAr')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="matchForm.competition_fr" :placeholder="t('sportsAmanah.competitionFr')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="matchForm.played_on" type="date" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="matchForm.venue" :placeholder="t('sportsAmanah.venue')" class="w-full rounded border px-3 py-2 text-sm" />
        <div class="grid grid-cols-2 gap-2">
          <input v-model="matchForm.goals_for" type="number" min="0" :placeholder="t('sportsAmanah.goalsFor')" class="rounded border px-2 py-2 text-sm" />
          <input v-model="matchForm.goals_against" type="number" min="0" :placeholder="t('sportsAmanah.goalsAgainst')" class="rounded border px-2 py-2 text-sm" />
        </div>
        <select v-model="matchForm.status" class="w-full rounded border px-3 py-2 text-sm">
          <option v-for="st in MATCH_STATUSES" :key="st" :value="st">{{ t(`sportsAmanah.matchStatuses.${st}`) }}</option>
        </select>
        <label class="flex items-center gap-2 text-sm"><input v-model="matchForm.is_home" type="checkbox" /> {{ t('sportsAmanah.home') }}</label>
        <label class="flex items-center gap-2 text-sm"><input v-model="matchForm.is_public" type="checkbox" /> {{ t('sportsAmanah.public') }}</label>
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white">{{ t('forms.save') }}</button>
      </form>
    </div>

    <div v-if="tab === 'trainings'" class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
      <div class="space-y-2">
        <article v-for="item in team?.trainings || []" :key="item.id" class="rounded border bg-white p-3">
          <p class="font-medium">{{ t(`sportsAmanah.weekdays.${item.weekday}`) }} · {{ item.starts_at }}-{{ item.ends_at }}</p>
          <p class="text-xs text-slate-600">{{ item.location }}</p>
          <div class="mt-1 flex gap-1">
            <button type="button" class="rounded border px-2 py-1 text-xs" @click="editTraining(item)">{{ t('forms.edit') }}</button>
            <button v-if="canDelete" type="button" class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700" @click="remove('training', item.id)">{{ t('forms.delete') }}</button>
          </div>
        </article>
      </div>
      <form class="space-y-2 rounded-xl border bg-white p-4" @submit.prevent="saveTraining">
        <h3 class="font-semibold">{{ t('sportsAmanah.newTraining') }}</h3>
        <select v-model.number="trainingForm.weekday" class="w-full rounded border px-3 py-2 text-sm">
          <option v-for="d in [1,2,3,4,5,6,7]" :key="d" :value="d">{{ t(`sportsAmanah.weekdays.${d}`) }}</option>
        </select>
        <input v-model="trainingForm.starts_at" :placeholder="t('sportsAmanah.startsAt')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="trainingForm.ends_at" :placeholder="t('sportsAmanah.endsAt')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="trainingForm.location" :placeholder="t('sportsAmanah.venue')" class="w-full rounded border px-3 py-2 text-sm" />
        <label class="flex items-center gap-2 text-sm"><input v-model="trainingForm.is_public" type="checkbox" /> {{ t('sportsAmanah.public') }}</label>
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white">{{ t('forms.save') }}</button>
      </form>
    </div>

    <div v-if="tab === 'tournaments'" class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
      <div class="space-y-2">
        <article v-for="item in team?.tournaments || []" :key="item.id" class="rounded border bg-white p-3">
          <p class="font-medium">{{ locale === 'ar' ? item.title_ar : item.title_fr }}</p>
          <p class="text-xs text-slate-600">{{ item.season }} · {{ item.ranking }}</p>
          <div class="mt-1 flex gap-1">
            <button type="button" class="rounded border px-2 py-1 text-xs" @click="editTournament(item)">{{ t('forms.edit') }}</button>
            <button v-if="canDelete" type="button" class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700" @click="remove('tournament', item.id)">{{ t('forms.delete') }}</button>
          </div>
        </article>
      </div>
      <form class="space-y-2 rounded-xl border bg-white p-4" @submit.prevent="saveTournament">
        <h3 class="font-semibold">{{ t('sportsAmanah.newTournament') }}</h3>
        <input v-model="tournamentForm.title_ar" required :placeholder="t('sportsAmanah.titleAr')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="tournamentForm.title_fr" required :placeholder="t('sportsAmanah.titleFr')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="tournamentForm.season" :placeholder="t('sportsAmanah.season')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="tournamentForm.location" :placeholder="t('sportsAmanah.venue')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="tournamentForm.ranking" :placeholder="t('sportsAmanah.ranking')" class="w-full rounded border px-3 py-2 text-sm" />
        <label class="flex items-center gap-2 text-sm"><input v-model="tournamentForm.is_public" type="checkbox" /> {{ t('sportsAmanah.public') }}</label>
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white">{{ t('forms.save') }}</button>
      </form>
    </div>
  </div>
</template>
