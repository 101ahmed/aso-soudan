<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  JOIN_STATUSES,
  deleteSportsJoinRequest,
  fetchSportsJoinRequests,
  updateSportsJoinRequest,
} from '@/services/sports'

const route = useRoute()
const { t } = useI18n()
const auth = useAuthStore()
const code = computed(() => route.params.code)
const loading = ref(false)
const error = ref('')
const items = ref([])
const filters = reactive({ search: '', status: '' })
const canUpdate = computed(() => auth.hasPermission('sport.update'))
const canDelete = computed(() => auth.hasPermission('sport.delete'))

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await fetchSportsJoinRequests(code.value, {
      search: filters.search || undefined,
      status: filters.status || undefined,
    })
    items.value = data.data || []
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

async function changeStatus(item, status) {
  if (!canUpdate.value) return
  await updateSportsJoinRequest(code.value, item.id, { status })
  await load()
}

async function saveNotes(item) {
  if (!canUpdate.value) return
  await updateSportsJoinRequest(code.value, item.id, { admin_notes: item.admin_notes, status: item.status })
  await load()
}

async function remove(id) {
  if (!canDelete.value) return
  if (!confirm(t('secretariatAdmin.confirmDelete'))) return
  await deleteSportsJoinRequest(code.value, id)
  await load()
}

watch([() => filters.search, () => filters.status], load)
onMounted(load)
</script>

<template>
  <div class="space-y-3">
    <h2 class="text-lg font-semibold">{{ t('sportsAmanah.joinAdminTitle') }}</h2>
    <p class="text-sm text-slate-600">{{ t('sportsAmanah.joinAdminHint') }}</p>
    <div class="grid gap-2 sm:grid-cols-2">
      <input v-model="filters.search" :placeholder="t('sportsAmanah.searchJoin')" class="rounded border px-3 py-2 text-sm" />
      <select v-model="filters.status" class="rounded border px-3 py-2 text-sm">
        <option value="">{{ t('sportsAmanah.allStatuses') }}</option>
        <option v-for="st in JOIN_STATUSES" :key="st" :value="st">{{ t(`sportsAmanah.joinStatuses.${st}`) }}</option>
      </select>
    </div>
    <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>
    <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>
    <article v-for="item in items" :key="item.id" class="rounded-lg border border-slate-200 bg-white p-4">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <p class="font-medium">{{ item.full_name }} <span class="text-xs text-slate-500">{{ item.reference }}</span></p>
          <p class="text-sm text-slate-600">{{ item.phone }} · {{ item.email }} · {{ item.city }}</p>
          <p class="text-xs text-slate-500">
            {{ item.age_category ? t(`sportsAmanah.ages.${item.age_category}`) : '' }}
            <span v-if="item.for_national"> · {{ t('sportsAmanah.nationalFlag') }}</span>
            <span v-if="item.team"> · {{ item.team.name_fr }}</span>
          </p>
          <p v-if="item.message" class="mt-2 text-sm">{{ item.message }}</p>
        </div>
        <div class="flex flex-col items-end gap-2">
          <select
            :value="item.status"
            class="rounded border px-2 py-1 text-xs"
            :disabled="!canUpdate"
            @change="changeStatus(item, $event.target.value)"
          >
            <option v-for="st in JOIN_STATUSES" :key="st" :value="st">{{ t(`sportsAmanah.joinStatuses.${st}`) }}</option>
          </select>
          <button v-if="canDelete" type="button" class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700" @click="remove(item.id)">{{ t('forms.delete') }}</button>
        </div>
      </div>
      <textarea
        :value="item.admin_notes || ''"
        rows="2"
        class="mt-3 w-full rounded border px-3 py-2 text-sm"
        :placeholder="t('sportsAmanah.adminNotes')"
        :disabled="!canUpdate"
        @change="item.admin_notes = $event.target.value; saveNotes(item)"
      />
    </article>
  </div>
</template>
