<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { pickName } from '@/utils/localized'
import {
  fetchSecretariatDirectiveTargets,
  fetchSecretariatDirectives,
  sendSecretariatDirective,
  updateSecretariatDirective,
} from '@/services/secretariatDirectives'

const route = useRoute()
const { t, locale } = useI18n()
const auth = useAuthStore()
const code = computed(() => route.params.code)

const loading = ref(false)
const sending = ref(false)
const error = ref('')
const success = ref('')
const items = ref([])
const targets = ref([])
const meta = ref({ current_page: 1, last_page: 1, total: 0 })
const statuses = ['sent', 'read', 'in_progress', 'done']
const tab = ref('received')

const canSend = computed(() =>
  auth.hasPermission('secretariat.directive.send')
  || auth.hasPermission('inbox.create')
  || auth.user?.roles?.some((r) => r.code === 'SUPER_ADMIN'),
)

const canUpdate = computed(() =>
  auth.hasPermission('secretariat.directive.view')
  || auth.hasPermission('inbox.update')
  || auth.user?.roles?.some((r) => ['SUPER_ADMIN', 'PRESIDENT', 'VICE_PRESIDENT'].includes(r.code)),
)

const filters = reactive({ search: '', status: '', classification: '', page: 1 })
const form = reactive({
  recipient: 'all',
  recipient_department_id: '',
  title: '',
  body: '',
  classification: 'info',
})

const selectedTarget = computed(() =>
  targets.value.find((item) => String(item.id) === String(form.recipient_department_id)),
)

function badgeClass(classification) {
  if (classification === 'urgent') return 'bg-red-100 text-red-800'
  if (classification === 'follow_up') return 'bg-amber-100 text-amber-900'
  return 'bg-emerald-100 text-emerald-800'
}

function formatDate(value) {
  if (!value) return ''
  return String(value).slice(0, 16).replace('T', ' ')
}

function resetForm() {
  form.recipient = 'all'
  form.recipient_department_id = ''
  form.title = ''
  form.body = ''
  form.classification = 'info'
}

async function loadTargets() {
  try {
    targets.value = await fetchSecretariatDirectiveTargets(code.value)
  } catch {
    targets.value = []
  }
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await fetchSecretariatDirectives(code.value, {
      direction: tab.value === 'sent' ? 'sent' : 'received',
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

async function submit() {
  if (!canSend.value) return
  sending.value = true
  error.value = ''
  success.value = ''
  try {
    await sendSecretariatDirective(code.value, {
      recipient: form.recipient,
      recipient_department_id: form.recipient === 'one' ? Number(form.recipient_department_id) : undefined,
      title: form.title || null,
      body: form.body,
      classification: form.classification,
    })
    success.value = form.recipient === 'all'
      ? t('secretariatAdmin.directiveSentAll')
      : t('secretariatAdmin.directiveSent')
    resetForm()
    tab.value = 'sent'
    filters.page = 1
    await load()
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || e.message
  } finally {
    sending.value = false
  }
}

async function changeStatus(item, status) {
  if (!canUpdate.value || tab.value !== 'received') return
  await updateSecretariatDirective(code.value, item.id, { status })
  await load()
}

async function saveNotes(item, notes) {
  if (!canUpdate.value || tab.value !== 'received') return
  await updateSecretariatDirective(code.value, item.id, { manager_notes: notes })
}

watch([() => filters.search, () => filters.status, () => filters.classification], () => {
  filters.page = 1
  load()
})
watch(() => code.value, async () => {
  filters.page = 1
  resetForm()
  await loadTargets()
  await load()
})
watch(tab, () => {
  filters.page = 1
  load()
})
watch(() => filters.page, load)
onMounted(async () => {
  await loadTargets()
  await load()
})
</script>

<template>
  <div class="space-y-5">
    <div>
      <h2 class="text-lg font-semibold">{{ t('secretariatAdmin.directives') }}</h2>
      <p class="text-sm text-slate-600">{{ t('secretariatAdmin.directivesHint') }}</p>
    </div>

    <form v-if="canSend" class="space-y-3 rounded-xl border bg-white p-5" @submit.prevent="submit">
      <h3 class="font-semibold text-[var(--rdp-forest)]">{{ t('secretariatAdmin.sendDirective') }}</h3>
      <p v-if="success" class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ success }}</p>
      <fieldset class="grid gap-2 sm:grid-cols-2">
        <label class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm" :class="form.recipient === 'all' ? 'border-teal-700 bg-teal-50' : 'border-slate-200'">
          <input v-model="form.recipient" type="radio" value="all" />
          {{ t('secretariatAdmin.sendToAll') }}
        </label>
        <label class="flex items-center gap-2 rounded-lg border px-3 py-2 text-sm" :class="form.recipient === 'one' ? 'border-teal-700 bg-teal-50' : 'border-slate-200'">
          <input v-model="form.recipient" type="radio" value="one" />
          {{ t('secretariatAdmin.sendToOne') }}
        </label>
      </fieldset>
      <select
        v-if="form.recipient === 'one'"
        v-model="form.recipient_department_id"
        required
        class="w-full rounded border px-3 py-2 text-sm"
      >
        <option value="" disabled>{{ t('secretariatAdmin.chooseRecipient') }}</option>
        <option v-for="dept in targets" :key="dept.id" :value="dept.id">
          {{ pickName(dept, locale) }}
        </option>
      </select>
      <p v-if="form.recipient === 'one' && selectedTarget" class="text-xs text-slate-500">
        {{ pickName(selectedTarget.officer, locale) || selectedTarget.officer?.email || '' }}
      </p>
      <input v-model="form.title" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('presidentAdmin.directiveTitle')" />
      <select v-model="form.classification" class="w-full rounded border px-3 py-2 text-sm">
        <option value="urgent">{{ t('presidentAdmin.classifications.urgent') }}</option>
        <option value="follow_up">{{ t('presidentAdmin.classifications.follow_up') }}</option>
        <option value="info">{{ t('presidentAdmin.classifications.info') }}</option>
      </select>
      <textarea
        v-model="form.body"
        required
        rows="5"
        class="w-full rounded border px-3 py-2 text-sm"
        :placeholder="t('presidentAdmin.directiveBody')"
      />
      <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white disabled:opacity-50" :disabled="sending">
        {{ sending ? t('admin.saving') : t('secretariatAdmin.sendDirective') }}
      </button>
    </form>

    <div class="flex flex-wrap gap-2">
      <button
        type="button"
        class="rounded-md px-3 py-1.5 text-sm"
        :class="tab === 'received' ? 'bg-teal-800 text-white' : 'bg-white text-slate-700'"
        @click="tab = 'received'"
      >
        {{ t('secretariatAdmin.directivesReceived') }}
      </button>
      <button
        type="button"
        class="rounded-md px-3 py-1.5 text-sm"
        :class="tab === 'sent' ? 'bg-teal-800 text-white' : 'bg-white text-slate-700'"
        @click="tab = 'sent'"
      >
        {{ t('secretariatAdmin.directivesSent') }}
      </button>
    </div>

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
        <div class="min-w-0 flex-1">
          <p class="text-xs text-slate-500">
            {{ item.reference }} · {{ formatDate(item.created_at) }}
            <span v-if="item.is_broadcast" class="ms-2 rounded bg-slate-100 px-1.5 py-0.5 text-[10px] font-medium text-slate-600">
              {{ t('secretariatAdmin.broadcastBadge') }}
            </span>
          </p>
          <p class="font-semibold text-[var(--rdp-forest)]">{{ item.title || t('presidentAdmin.directiveFallbackTitle') }}</p>
          <p class="mt-1 text-xs text-slate-500">
            {{ t('secretariatAdmin.fromSecretariat') }}:
            {{ pickName(item.sender_department, locale) }}
            <span v-if="item.sender?.name"> · {{ item.sender.name }}</span>
            · {{ t('secretariatAdmin.toSecretariat') }}:
            {{ pickName(item.recipient_department, locale) }}
          </p>
          <p class="mt-2 whitespace-pre-line text-sm text-slate-700">{{ item.body }}</p>
          <label v-if="canUpdate && tab === 'received'" class="mt-3 block text-sm">
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
            v-if="canUpdate && tab === 'received'"
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

    <p v-if="!loading && !items.length" class="text-sm text-slate-500">
      {{ tab === 'sent' ? t('secretariatAdmin.emptySentDirectives') : t('secretariatAdmin.emptyReceivedDirectives') }}
    </p>
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
