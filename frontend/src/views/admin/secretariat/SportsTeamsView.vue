<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { pickName } from '@/utils/localized'
import {
  AGE_CATEGORIES,
  createSportsTeam,
  deleteSportsTeam,
  fetchSportsTeams,
  updateSportsTeam,
} from '@/services/sports'

const route = useRoute()
const { t, locale } = useI18n()
const auth = useAuthStore()
const code = computed(() => route.params.code)

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const items = ref([])
const editingId = ref(null)
const photoFile = ref(null)
const filters = reactive({ search: '' })
const form = reactive(emptyForm())

const canCreate = computed(() => auth.hasPermission('sport.create'))
const canUpdate = computed(() => auth.hasPermission('sport.update'))
const canDelete = computed(() => auth.hasPermission('sport.delete'))
const canManage = computed(() => (editingId.value ? canUpdate.value : canCreate.value))

function emptyForm() {
  return {
    name_ar: '',
    name_fr: '',
    age_category: 'u13',
    coach_ar: '',
    coach_fr: '',
    manager_ar: '',
    manager_fr: '',
    ranking: '',
    points: '',
    played: '',
    wins: '',
    draws: '',
    losses: '',
    goals_for: '',
    goals_against: '',
    notes_ar: '',
    notes_fr: '',
    is_public: false,
  }
}

function resetForm() {
  editingId.value = null
  photoFile.value = null
  Object.assign(form, emptyForm())
}

function edit(item) {
  editingId.value = item.id
  photoFile.value = null
  Object.assign(form, {
    name_ar: item.name_ar || '',
    name_fr: item.name_fr || '',
    age_category: item.age_category || 'u13',
    coach_ar: item.coach_ar || '',
    coach_fr: item.coach_fr || '',
    manager_ar: item.manager_ar || '',
    manager_fr: item.manager_fr || '',
    ranking: item.ranking ?? '',
    points: item.points ?? '',
    played: item.played ?? '',
    wins: item.wins ?? '',
    draws: item.draws ?? '',
    losses: item.losses ?? '',
    goals_for: item.goals_for ?? '',
    goals_against: item.goals_against ?? '',
    notes_ar: item.notes_ar || '',
    notes_fr: item.notes_fr || '',
    is_public: !!item.is_public,
  })
}

function payload() {
  return {
    ...form,
    photo: photoFile.value || undefined,
  }
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await fetchSportsTeams(code.value, { search: filters.search || undefined })
    items.value = data.data || []
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

async function save() {
  if (!canManage.value) return
  saving.value = true
  error.value = ''
  try {
    if (editingId.value) await updateSportsTeam(code.value, editingId.value, payload())
    else await createSportsTeam(code.value, payload())
    resetForm()
    await load()
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || e.message
  } finally {
    saving.value = false
  }
}

async function remove(id) {
  if (!canDelete.value) return
  if (!confirm(t('secretariatAdmin.confirmDelete'))) return
  await deleteSportsTeam(code.value, id)
  await load()
}

watch(() => filters.search, load)
onMounted(load)
</script>

<template>
  <div class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
    <div class="space-y-3">
      <h2 class="text-lg font-semibold">{{ t('sportsAmanah.teamsTitle') }}</h2>
      <p class="text-sm text-slate-600">{{ t('sportsAmanah.teamsHint') }}</p>
      <input v-model="filters.search" :placeholder="t('sportsAmanah.searchTeams')" class="w-full rounded border px-3 py-2 text-sm" />
      <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>
      <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>
      <article v-for="item in items" :key="item.id" class="flex gap-3 rounded-lg border border-slate-200 bg-white p-4">
        <img
          v-if="item.photo_url"
          :src="item.photo_url"
          alt=""
          class="h-16 w-16 rounded object-cover"
        />
        <div class="min-w-0 flex-1">
          <p class="font-medium">{{ pickName(item, locale) }}</p>
          <p class="text-sm text-slate-600">
            {{ t(`sportsAmanah.ages.${item.age_category}`) }}
            · {{ item.players_count || 0 }} {{ t('sportsAmanah.players') }}
          </p>
          <p class="text-xs text-slate-500">
            {{ t('sportsAmanah.coach') }}: {{ locale === 'ar' ? item.coach_ar : item.coach_fr }}
            · {{ t('sportsAmanah.manager') }}: {{ locale === 'ar' ? item.manager_ar : item.manager_fr }}
          </p>
        </div>
        <div class="flex flex-col items-end gap-1">
          <span v-if="item.is_public" class="text-xs text-emerald-700">{{ t('sportsAmanah.public') }}</span>
          <RouterLink :to="`${route.path}/${item.id}`" class="rounded border px-2 py-1 text-xs">{{ t('sportsAmanah.openTeam') }}</RouterLink>
          <button type="button" class="rounded border px-2 py-1 text-xs" @click="edit(item)">{{ t('forms.edit') }}</button>
          <button
            v-if="canDelete"
            type="button"
            class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700"
            @click="remove(item.id)"
          >
            {{ t('forms.delete') }}
          </button>
        </div>
      </article>
    </div>

    <form class="space-y-3 rounded-xl border border-slate-200 bg-white p-5" @submit.prevent="save">
      <h3 class="font-semibold">{{ editingId ? t('sportsAmanah.editTeam') : t('sportsAmanah.newTeam') }}</h3>
      <input v-model="form.name_ar" required :placeholder="t('sportsAmanah.nameAr')" class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.name_fr" required :placeholder="t('sportsAmanah.nameFr')" class="w-full rounded border px-3 py-2 text-sm" />
      <select v-model="form.age_category" class="w-full rounded border px-3 py-2 text-sm">
        <option v-for="age in AGE_CATEGORIES" :key="age" :value="age">{{ t(`sportsAmanah.ages.${age}`) }}</option>
      </select>
      <input v-model="form.coach_ar" :placeholder="t('sportsAmanah.coachAr')" class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.coach_fr" :placeholder="t('sportsAmanah.coachFr')" class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.manager_ar" :placeholder="t('sportsAmanah.managerAr')" class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.manager_fr" :placeholder="t('sportsAmanah.managerFr')" class="w-full rounded border px-3 py-2 text-sm" />
      <div class="grid grid-cols-3 gap-2">
        <input v-model="form.ranking" type="number" min="1" :placeholder="t('sportsAmanah.ranking')" class="rounded border px-2 py-2 text-sm" />
        <input v-model="form.points" type="number" min="0" :placeholder="t('sportsAmanah.points')" class="rounded border px-2 py-2 text-sm" />
        <input v-model="form.played" type="number" min="0" :placeholder="t('sportsAmanah.played')" class="rounded border px-2 py-2 text-sm" />
        <input v-model="form.wins" type="number" min="0" :placeholder="t('sportsAmanah.wins')" class="rounded border px-2 py-2 text-sm" />
        <input v-model="form.draws" type="number" min="0" :placeholder="t('sportsAmanah.draws')" class="rounded border px-2 py-2 text-sm" />
        <input v-model="form.losses" type="number" min="0" :placeholder="t('sportsAmanah.losses')" class="rounded border px-2 py-2 text-sm" />
        <input v-model="form.goals_for" type="number" min="0" :placeholder="t('sportsAmanah.goalsFor')" class="rounded border px-2 py-2 text-sm" />
        <input v-model="form.goals_against" type="number" min="0" :placeholder="t('sportsAmanah.goalsAgainst')" class="rounded border px-2 py-2 text-sm" />
      </div>
      <textarea v-model="form.notes_ar" rows="2" :placeholder="t('sportsAmanah.notesAr')" class="w-full rounded border px-3 py-2 text-sm" />
      <textarea v-model="form.notes_fr" rows="2" :placeholder="t('sportsAmanah.notesFr')" class="w-full rounded border px-3 py-2 text-sm" />
      <input type="file" accept="image/*" class="w-full text-sm" @change="photoFile = $event.target.files?.[0] || null" />
      <label class="flex items-center gap-2 text-sm">
        <input v-model="form.is_public" type="checkbox" />
        {{ t('sportsAmanah.public') }}
      </label>
      <div class="flex gap-2">
        <button type="submit" :disabled="saving || !canManage" class="rounded bg-teal-800 px-4 py-2 text-sm text-white disabled:opacity-50">
          {{ t('forms.save') }}
        </button>
        <button v-if="editingId" type="button" class="rounded border px-4 py-2 text-sm" @click="resetForm">{{ t('forms.cancel') }}</button>
      </div>
    </form>
  </div>
</template>
