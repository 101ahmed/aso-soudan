<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { pickName, pickTitle } from '@/utils/localized'
import { fetchPublicDepartments } from '@/services/content'
import {
  DECISION_KINDS,
  DECISION_STATUSES,
  createMediaDecision,
  deleteMediaDecision,
  fetchMediaDecisions,
  updateMediaDecision,
} from '@/services/decisions'
import { isSecretariatCode } from '@/utils/departmentAccess'

const route = useRoute()
const { t, locale } = useI18n()
const auth = useAuthStore()
const code = computed(() => route.params.code)
const items = ref([])
const departments = ref([])
const loading = ref(false)
const error = ref('')
const editingId = ref(null)
const formBox = ref(null)

const canCreate = computed(() => auth.hasPermission('decision.create'))
const canUpdate = computed(() => auth.hasPermission('decision.update'))
const canDelete = computed(() => auth.hasPermission('decision.delete'))
const canManage = computed(() => (editingId.value ? canUpdate.value : canCreate.value))
const isGeneral = computed(() => code.value === 'general')

const secretariatOptions = computed(() =>
  (departments.value || []).filter((dept) => isSecretariatCode(dept.code)),
)

const filters = reactive({ search: '', kind: '', status: '' })
const form = reactive(emptyForm())

function emptyForm() {
  return {
    kind: 'decision',
    title_ar: '',
    title_fr: '',
    details_ar: '',
    details_fr: '',
    responsible_ar: '',
    responsible_fr: '',
    department_id: '',
    decided_on: new Date().toISOString().slice(0, 10),
    due_on: '',
    status: 'pending',
    show_on_home: code.value === 'general',
    notes: '',
  }
}

function resetForm() {
  editingId.value = null
  Object.assign(form, emptyForm())
}

function startNew() {
  resetForm()
  formBox.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

function responsibleOf(item) {
  return locale.value === 'ar'
    ? item.responsible_ar || item.responsible_fr
    : item.responsible_fr || item.responsible_ar
}

function departmentOf(item) {
  if (!item.department) return ''
  return pickName(item.department, locale.value)
}

function statusClass(item) {
  if (item.status === 'done') return 'bg-emerald-50 text-emerald-800'
  if (item.status === 'cancelled') return 'bg-slate-100 text-slate-600'
  if (item.status === 'delayed' || item.overdue) return 'bg-rose-50 text-rose-800'
  if (item.status === 'in_progress') return 'bg-amber-50 text-amber-900'
  return 'bg-sky-50 text-sky-800'
}

function edit(item) {
  editingId.value = item.id
  form.kind = item.kind
  form.title_ar = item.title_ar
  form.title_fr = item.title_fr
  form.details_ar = item.details_ar || ''
  form.details_fr = item.details_fr || ''
  form.responsible_ar = item.responsible_ar
  form.responsible_fr = item.responsible_fr
  form.department_id = item.department_id || ''
  form.decided_on = item.decided_on
  form.due_on = item.due_on || ''
  form.status = item.status
  form.show_on_home = !!item.show_on_home
  form.notes = item.notes || ''
  formBox.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await fetchMediaDecisions(code.value, {
      search: filters.search || undefined,
      kind: filters.kind || undefined,
      status: filters.status || undefined,
    })
    items.value = data.data || []
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

async function save() {
  if (!canManage.value) return
  error.value = ''
  try {
    const payload = {
      ...form,
      department_id: form.department_id || null,
      due_on: form.due_on || null,
    }
    if (editingId.value) await updateMediaDecision(code.value, editingId.value, payload)
    else await createMediaDecision(code.value, payload)
    resetForm()
    await load()
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || e.message
  }
}

async function changeStatus(item, status) {
  if (!canUpdate.value) return
  await updateMediaDecision(code.value, item.id, { status })
  await load()
}

async function remove(id) {
  if (!canDelete.value) return
  if (!confirm(t('secretariatAdmin.confirmDelete'))) return
  await deleteMediaDecision(code.value, id)
  await load()
}

watch([() => filters.search, () => filters.kind, () => filters.status], load)
onMounted(async () => {
  try {
    departments.value = await fetchPublicDepartments()
  } catch {
    departments.value = []
  }
  await load()
})
</script>

<template>
  <div class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
    <div class="space-y-3">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h2 class="text-lg font-semibold">📋 {{ isGeneral ? t('mediaDecisions.executiveTitle') : t('mediaDecisions.title') }}</h2>
          <p class="mt-1 text-sm text-slate-600">{{ isGeneral ? t('mediaDecisions.executiveHint') : t('mediaDecisions.hint') }}</p>
        </div>
        <button
          v-if="canCreate"
          type="button"
          class="rounded bg-teal-800 px-3 py-2 text-sm font-semibold text-white"
          @click="startNew"
        >
          ➕ {{ t('mediaDecisions.new') }}
        </button>
      </div>

      <div class="grid gap-2 sm:grid-cols-3">
        <input v-model="filters.search" :placeholder="t('mediaDecisions.search')" class="rounded border px-3 py-2 text-sm" />
        <select v-model="filters.kind" class="rounded border px-3 py-2 text-sm">
          <option value="">{{ t('mediaDecisions.allKinds') }}</option>
          <option v-for="kind in DECISION_KINDS" :key="kind" :value="kind">{{ t(`mediaDecisions.kinds.${kind}`) }}</option>
        </select>
        <select v-model="filters.status" class="rounded border px-3 py-2 text-sm">
          <option value="">{{ t('mediaDecisions.allStatuses') }}</option>
          <option v-for="status in DECISION_STATUSES" :key="status" :value="status">{{ t(`mediaDecisions.statuses.${status}`) }}</option>
        </select>
      </div>

      <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>
      <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>
      <p v-else-if="!loading && !items.length" class="text-sm text-slate-500">{{ t('mediaDecisions.empty') }}</p>

      <article v-for="item in items" :key="item.id" class="rounded-lg border border-slate-200 bg-white p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p class="text-xs text-slate-500">{{ item.reference }} · {{ t(`mediaDecisions.kinds.${item.kind}`) }}</p>
            <p class="font-medium">{{ pickTitle(item, locale) }}</p>
            <p class="mt-2 text-sm text-slate-700">👤 {{ responsibleOf(item) }}</p>
            <p v-if="departmentOf(item)" class="text-xs text-slate-500">{{ departmentOf(item) }}</p>
            <p class="mt-2 text-sm text-slate-600">📅 {{ t('mediaDecisions.decidedOn') }}: {{ item.decided_on }}</p>
            <p v-if="item.show_on_home" class="mt-1 text-xs font-medium text-teal-800">{{ t('secretariatAdmin.showOnHome') }}</p>
            <p class="text-sm text-slate-600">⏳ {{ t('mediaDecisions.dueOn') }}: {{ item.due_on || '—' }}</p>
          </div>
          <div class="flex flex-col items-end gap-2">
            <span class="rounded px-2 py-1 text-xs font-medium" :class="statusClass(item)">
              🔄 {{ t(`mediaDecisions.statuses.${item.status}`) }}
              <span v-if="item.overdue && item.status !== 'delayed'"> · {{ t('mediaDecisions.overdue') }}</span>
            </span>
            <select
              v-if="canUpdate"
              class="rounded border px-2 py-1 text-xs"
              :value="item.status"
              @change="changeStatus(item, $event.target.value)"
            >
              <option v-for="status in DECISION_STATUSES" :key="status" :value="status">
                {{ t(`mediaDecisions.statuses.${status}`) }}
              </option>
            </select>
            <div class="flex gap-1">
              <button v-if="canUpdate" type="button" class="rounded border px-2 py-1 text-xs" @click="edit(item)">{{ t('forms.edit') }}</button>
              <button v-if="canDelete" type="button" class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700" @click="remove(item.id)">{{ t('forms.delete') }}</button>
            </div>
          </div>
        </div>
      </article>
    </div>

    <form
      v-if="canManage"
      ref="formBox"
      class="space-y-3 rounded-xl border bg-white p-5"
      @submit.prevent="save"
    >
      <h3 class="font-semibold">{{ editingId ? t('mediaDecisions.edit') : t('mediaDecisions.new') }}</h3>
      <select v-model="form.kind" class="w-full rounded border px-3 py-2 text-sm">
        <option v-for="kind in DECISION_KINDS" :key="kind" :value="kind">{{ t(`mediaDecisions.kinds.${kind}`) }}</option>
      </select>
      <input v-model="form.title_fr" required class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.titleFr')" />
      <input v-model="form.title_ar" required dir="rtl" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.titleAr')" />
      <textarea v-model="form.details_fr" rows="3" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('mediaDecisions.detailsFr')" />
      <textarea v-model="form.details_ar" rows="3" dir="rtl" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('mediaDecisions.detailsAr')" />
      <label class="block text-sm">
        <span class="mb-1 block text-slate-600">👤 {{ t('mediaDecisions.responsible') }} (FR)</span>
        <input v-model="form.responsible_fr" required class="w-full rounded border px-3 py-2 text-sm" />
      </label>
      <label class="block text-sm">
        <span class="mb-1 block text-slate-600">👤 {{ t('mediaDecisions.responsible') }} (AR)</span>
        <input v-model="form.responsible_ar" required dir="rtl" class="w-full rounded border px-3 py-2 text-sm" />
      </label>
      <select v-model="form.department_id" class="w-full rounded border px-3 py-2 text-sm">
        <option value="">{{ t('mediaDecisions.noDepartment') }}</option>
        <option v-for="dept in secretariatOptions" :key="dept.id" :value="dept.id">{{ pickName(dept, locale) }}</option>
      </select>
      <label class="block text-sm">
        <span class="mb-1 block text-slate-600">📅 {{ t('mediaDecisions.decidedOn') }}</span>
        <input v-model="form.decided_on" type="date" required class="w-full rounded border px-3 py-2 text-sm" />
      </label>
      <label class="block text-sm">
        <span class="mb-1 block text-slate-600">⏳ {{ t('mediaDecisions.dueOn') }}</span>
        <input v-model="form.due_on" type="date" class="w-full rounded border px-3 py-2 text-sm" />
      </label>
      <label class="block text-sm">
        <span class="mb-1 block text-slate-600">🔄 {{ t('mediaDecisions.status') }}</span>
        <select v-model="form.status" class="w-full rounded border px-3 py-2 text-sm">
          <option v-for="status in DECISION_STATUSES" :key="status" :value="status">{{ t(`mediaDecisions.statuses.${status}`) }}</option>
        </select>
      </label>
      <textarea v-model="form.notes" rows="2" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('mediaDecisions.notes')" />
      <label class="flex items-center gap-2 text-sm">
        <input v-model="form.show_on_home" type="checkbox" />
        {{ t('secretariatAdmin.showOnHome') }}
      </label>
      <div class="flex gap-2">
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white">{{ t('forms.save') }}</button>
        <button type="button" class="rounded border px-4 py-2 text-sm" @click="resetForm">{{ t('forms.cancel') }}</button>
      </div>
    </form>
  </div>
</template>
