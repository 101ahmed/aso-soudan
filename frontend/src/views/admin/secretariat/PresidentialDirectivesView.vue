<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { pickName } from '@/utils/localized'
import {
  fetchSecretariatPresidentialDirectives,
  updateSecretariatPresidentialDirective,
} from '@/services/president'

const route = useRoute()
const { t, locale } = useI18n()
const auth = useAuthStore()
const code = computed(() => route.params.code)

const loading = ref(false)
const error = ref('')
const items = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const statuses = ['sent', 'read', 'in_progress', 'done']

const canUpdate = computed(() =>
  auth.hasPermission('president.directive.inbox')
  || auth.hasPermission('inbox.update')
  || auth.user?.roles?.some((r) => ['SUPER_ADMIN', 'PRESIDENT'].includes(r.code)),
)

const filters = reactive({ search: '', status: '', classification: '', page: 1 })

function badgeClass(classification) {
  if (classification === 'urgent') return 'bg-red-100 text-red-800'
  if (classification === 'follow_up') return 'bg-amber-100 text-amber-900'
  return 'bg-emerald-100 text-emerald-800'
}

function formatDate(value) {
  if (!value) return ''
  return String(value).slice(0, 16).replace('T', ' ')
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await fetchSecretariatPresidentialDirectives(code.value, {
      search: filters.search || undefined,
      status: filters.status || undefined,
      classification: filters.classification || undefined,
      page: filters.page,
    })
    items.value = data.data || []
    meta.value = data.meta || { current_page: 1, last_page: 1, total: items.value.length }
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

async function changeStatus(item, status) {
  if (!canUpdate.value) return
  await updateSecretariatPresidentialDirective(code.value, item.id, { status })
  await load()
}

async function saveNotes(item, notes) {
  if (!canUpdate.value) return
  await updateSecretariatPresidentialDirective(code.value, item.id, { manager_notes: notes })
}

watch([() => filters.search, () => filters.status, () => filters.classification], () => {
  filters.page = 1
  load()
})
watch(() => code.value, () => {
  filters.page = 1
  load()
})
watch(() => filters.page, load)
onMounted(load)
</script>

<template>
  <div class="space-y-3">
    <h2 class="text-lg font-semibold">{{ t('presidentAdmin.inboxTitle') }}</h2>
    <p class="text-sm text-slate-600">{{ t('presidentAdmin.inboxHint') }}</p>

    <div class="grid gap-2 sm:grid-cols-3">
      <input v-model="filters.search" :placeholder="t('presidentAdmin.searchDirectives')" class="rounded border px-3 py-2 text-sm" />
      <select v-model="filters.status" class="rounded border px-3 py-2 text-sm">
        <option value="">{{ t('presidentAdmin.allStatuses') }}</option>
        <option v-for="status in statuses" :key="status" :value="status">{{ t(`presidentAdmin.directiveStatuses.${status}`) }}</option>
      </select>
      <select v-model="filters.classification" class="rounded border px-3 py-2 text-sm">
        <option value="">{{ t('presidentAdmin.allClassifications') }}</option>
        <option value="urgent">{{ t('presidentAdmin.classifications.urgent') }}</option>
        <option value="follow_up">{{ t('presidentAdmin.classifications.follow_up') }}</option>
        <option value="info">{{ t('presidentAdmin.classifications.info') }}</option>
      </select>
    </div>

    <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>
    <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>

    <article v-for="item in items" :key="item.id" class="rounded-lg border border-slate-200 bg-white p-4">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <p class="text-xs text-slate-500">{{ item.reference }} · {{ formatDate(item.created_at) }}</p>
          <p class="font-semibold text-[var(--rdp-forest)]">{{ item.title || t('presidentAdmin.directiveFallbackTitle') }}</p>
          <p class="mt-1 text-xs text-slate-500">
            {{ t('presidentAdmin.fromPresident') }}
            <span v-if="item.sender?.name"> · {{ item.sender.name }}</span>
            <span v-if="item.assignee?.name"> · {{ t('presidentAdmin.assignedTo') }}: {{ item.assignee.name }}</span>
            <span v-else-if="item.department?.officer"> · {{ t('presidentAdmin.assignedTo') }}: {{ pickName(item.department.officer, locale) || pickName(item.department, locale) }}</span>
            <span v-else-if="item.department"> · {{ pickName(item.department, locale) }}</span>
          </p>
          <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ item.body }}</p>
          <label v-if="canUpdate" class="mt-3 block text-sm">
            <span class="mb-1 block text-slate-500">{{ t('presidentAdmin.managerNotes') }}</span>
            <textarea
              :value="item.manager_notes || ''"
              rows="2"
              class="w-full rounded border px-3 py-2 text-sm"
              @change="saveNotes(item, $event.target.value)"
            />
          </label>
          <p v-else-if="item.manager_notes" class="mt-2 text-sm text-slate-600">{{ item.manager_notes }}</p>
        </div>
        <div class="flex flex-col items-end gap-2">
          <span class="rounded px-2 py-1 text-xs font-medium" :class="badgeClass(item.classification)">
            {{ t(`presidentAdmin.classifications.${item.classification}`) }}
          </span>
          <span class="text-xs text-slate-500">{{ t(`presidentAdmin.directiveStatuses.${item.status}`) }}</span>
          <select
            v-if="canUpdate"
            class="rounded border px-2 py-1 text-xs"
            :value="item.status"
            @change="changeStatus(item, $event.target.value)"
          >
            <option v-for="status in statuses" :key="status" :value="status">
              {{ t(`presidentAdmin.directiveStatuses.${status}`) }}
            </option>
          </select>
        </div>
      </div>
    </article>

    <p v-if="!loading && !items.length" class="text-sm text-slate-500">{{ t('presidentAdmin.emptyInbox') }}</p>
    <div v-if="meta.last_page > 1" class="flex items-center gap-2 text-sm">
      <button type="button" class="rounded border px-3 py-1 disabled:opacity-40" :disabled="meta.current_page <= 1" @click="filters.page -= 1">
        {{ t('admin.prev') }}
      </button>
      <span>{{ meta.current_page }} / {{ meta.last_page }}</span>
      <button type="button" class="rounded border px-3 py-1 disabled:opacity-40" :disabled="meta.current_page >= meta.last_page" @click="filters.page += 1">
        {{ t('admin.next') }}
      </button>
    </div>
  </div>
</template>
