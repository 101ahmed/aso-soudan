<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { pickContent, pickTitle } from '@/utils/localized'
import {
  PRESS_KINDS,
  PRESS_STATUSES,
  archiveMediaCenterItem,
  createMediaCenterItem,
  deleteMediaCenterItem,
  fetchMediaCenter,
  publishMediaCenterItem,
  updateMediaCenterItem,
} from '@/services/mediaCenter'

const KIND_ICONS = {
  official: '📄',
  statement: '🎙️',
  coverage: '📰',
  conference: '🎤',
  interview: '💬',
}

const route = useRoute()
const { t, locale } = useI18n()
const auth = useAuthStore()
const code = computed(() => route.params.code)
const items = ref([])
const loading = ref(false)
const error = ref('')
const editingId = ref(null)
const formBox = ref(null)
const fileInput = ref(null)

const canCreate = computed(() => auth.hasPermission('press.create'))
const canUpdate = computed(() => auth.hasPermission('press.update'))
const canDelete = computed(() => auth.hasPermission('press.delete'))
const canManage = computed(() => (editingId.value ? canUpdate.value : canCreate.value))

const filters = reactive({ search: '', kind: '', status: '' })
const form = reactive(emptyForm())

function emptyForm() {
  return {
    kind: filters.kind || 'official',
    title_ar: '',
    title_fr: '',
    content_ar: '',
    content_fr: '',
    source_ar: '',
    source_fr: '',
    person_ar: '',
    person_fr: '',
    location_ar: '',
    location_fr: '',
    external_url: '',
    occurred_on: new Date().toISOString().slice(0, 10),
    status: 'draft',
    image: null,
  }
}

function resetForm() {
  editingId.value = null
  Object.assign(form, emptyForm())
  if (fileInput.value) fileInput.value.value = ''
}

function startNew() {
  resetForm()
  formBox.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

function selectKind(kind) {
  filters.kind = filters.kind === kind ? '' : kind
  if (!editingId.value) form.kind = filters.kind || 'official'
}

function personOf(item) {
  return locale.value === 'ar' ? item.person_ar || item.person_fr : item.person_fr || item.person_ar
}

function sourceOf(item) {
  return locale.value === 'ar' ? item.source_ar || item.source_fr : item.source_fr || item.source_ar
}

function locationOf(item) {
  return locale.value === 'ar' ? item.location_ar || item.location_fr : item.location_fr || item.location_ar
}

function statusClass(status) {
  if (status === 'published') return 'bg-emerald-50 text-emerald-800'
  if (status === 'archived') return 'bg-slate-100 text-slate-600'
  if (status === 'pending_review') return 'bg-amber-50 text-amber-900'
  return 'bg-sky-50 text-sky-800'
}

function edit(item) {
  editingId.value = item.id
  form.kind = item.kind
  form.title_ar = item.title_ar
  form.title_fr = item.title_fr
  form.content_ar = item.content_ar || ''
  form.content_fr = item.content_fr || ''
  form.source_ar = item.source_ar || ''
  form.source_fr = item.source_fr || ''
  form.person_ar = item.person_ar || ''
  form.person_fr = item.person_fr || ''
  form.location_ar = item.location_ar || ''
  form.location_fr = item.location_fr || ''
  form.external_url = item.external_url || ''
  form.occurred_on = item.occurred_on || ''
  form.status = item.status
  form.image = null
  if (fileInput.value) fileInput.value.value = ''
  formBox.value?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await fetchMediaCenter(code.value, {
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
    const payload = { ...form }
    if (editingId.value) await updateMediaCenterItem(code.value, editingId.value, payload)
    else await createMediaCenterItem(code.value, payload)
    resetForm()
    await load()
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || e.message
  }
}

async function publish(id) {
  if (!canUpdate.value) return
  await publishMediaCenterItem(code.value, id)
  await load()
}

async function archive(id) {
  if (!canUpdate.value) return
  await archiveMediaCenterItem(code.value, id)
  await load()
}

async function remove(id) {
  if (!canDelete.value) return
  if (!confirm(t('secretariatAdmin.confirmDelete'))) return
  await deleteMediaCenterItem(code.value, id)
  await load()
}

watch([() => filters.search, () => filters.kind, () => filters.status], load)
onMounted(load)
</script>

<template>
  <div class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
    <div class="space-y-3">
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h2 class="text-lg font-semibold">📢 {{ t('mediaCenter.title') }}</h2>
          <p class="mt-1 text-sm text-slate-600">{{ t('mediaCenter.hint') }}</p>
        </div>
        <button
          v-if="canCreate"
          type="button"
          class="rounded bg-teal-800 px-3 py-2 text-sm font-semibold text-white"
          @click="startNew"
        >
          ➕ {{ t('mediaCenter.new') }}
        </button>
      </div>

      <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
        <button
          v-for="kind in PRESS_KINDS"
          :key="kind"
          type="button"
          class="rounded-lg border px-3 py-2 text-start text-sm"
          :class="filters.kind === kind ? 'border-teal-800 bg-teal-800 text-white' : 'border-slate-200 bg-white text-slate-700 hover:border-teal-700/40'"
          @click="selectKind(kind)"
        >
          {{ KIND_ICONS[kind] }} {{ t(`mediaCenter.kinds.${kind}`) }}
        </button>
      </div>

      <div class="grid gap-2 sm:grid-cols-2">
        <input v-model="filters.search" :placeholder="t('mediaCenter.search')" class="rounded border px-3 py-2 text-sm" />
        <select v-model="filters.status" class="rounded border px-3 py-2 text-sm">
          <option value="">{{ t('mediaCenter.allStatuses') }}</option>
          <option v-for="status in PRESS_STATUSES" :key="status" :value="status">
            {{ t(`mediaCenter.statuses.${status}`) }}
          </option>
        </select>
      </div>

      <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>
      <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>
      <p v-else-if="!loading && !items.length" class="text-sm text-slate-500">{{ t('mediaCenter.empty') }}</p>

      <article v-for="item in items" :key="item.id" class="rounded-lg border border-slate-200 bg-white p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="text-xs text-slate-500">
              {{ KIND_ICONS[item.kind] }} {{ t(`mediaCenter.kinds.${item.kind}`) }}
            </p>
            <p class="font-medium">{{ pickTitle(item, locale) }}</p>
            <p v-if="pickContent(item, locale)" class="mt-1 line-clamp-2 text-sm text-slate-600">
              {{ pickContent(item, locale) }}
            </p>
            <p v-if="personOf(item)" class="mt-2 text-sm text-slate-700">👤 {{ personOf(item) }}</p>
            <p v-if="sourceOf(item)" class="text-sm text-slate-600">📰 {{ sourceOf(item) }}</p>
            <p v-if="locationOf(item)" class="text-sm text-slate-600">📍 {{ locationOf(item) }}</p>
            <p class="mt-2 text-sm text-slate-600">📅 {{ item.occurred_on || '—' }}</p>
          </div>
          <div class="flex flex-col items-end gap-2">
            <span class="rounded px-2 py-1 text-xs font-medium" :class="statusClass(item.status)">
              {{ t(`mediaCenter.statuses.${item.status}`) }}
            </span>
            <div class="flex flex-wrap justify-end gap-1">
              <button v-if="canUpdate" type="button" class="rounded border px-2 py-1 text-xs" @click="edit(item)">
                {{ t('forms.edit') }}
              </button>
              <button
                v-if="canUpdate && item.status !== 'published'"
                type="button"
                class="rounded border px-2 py-1 text-xs"
                @click="publish(item.id)"
              >
                {{ t('secretariatAdmin.publish') }}
              </button>
              <button
                v-if="canUpdate && item.status !== 'archived'"
                type="button"
                class="rounded border px-2 py-1 text-xs"
                @click="archive(item.id)"
              >
                {{ t('secretariatAdmin.archive') }}
              </button>
              <button
                v-if="canDelete"
                type="button"
                class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700"
                @click="remove(item.id)"
              >
                {{ t('forms.delete') }}
              </button>
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
      <h3 class="font-semibold">{{ editingId ? t('mediaCenter.edit') : t('mediaCenter.new') }}</h3>
      <select v-model="form.kind" class="w-full rounded border px-3 py-2 text-sm">
        <option v-for="kind in PRESS_KINDS" :key="kind" :value="kind">
          {{ KIND_ICONS[kind] }} {{ t(`mediaCenter.kinds.${kind}`) }}
        </option>
      </select>
      <input v-model="form.title_fr" required class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.titleFr')" />
      <input v-model="form.title_ar" required dir="rtl" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.titleAr')" />
      <textarea v-model="form.content_fr" rows="4" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('mediaCenter.contentFr')" />
      <textarea v-model="form.content_ar" rows="4" dir="rtl" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('mediaCenter.contentAr')" />
      <input v-model="form.source_fr" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('mediaCenter.sourceFr')" />
      <input v-model="form.source_ar" dir="rtl" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('mediaCenter.sourceAr')" />
      <input v-model="form.person_fr" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('mediaCenter.personFr')" />
      <input v-model="form.person_ar" dir="rtl" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('mediaCenter.personAr')" />
      <input v-model="form.location_fr" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('mediaCenter.locationFr')" />
      <input v-model="form.location_ar" dir="rtl" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('mediaCenter.locationAr')" />
      <input v-model="form.external_url" type="url" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('mediaCenter.externalUrl')" />
      <label class="block text-sm">
        <span class="mb-1 block text-slate-600">📅 {{ t('mediaCenter.occurredOn') }}</span>
        <input v-model="form.occurred_on" type="date" class="w-full rounded border px-3 py-2 text-sm" />
      </label>
      <select v-model="form.status" class="w-full rounded border px-3 py-2 text-sm">
        <option v-for="status in PRESS_STATUSES" :key="status" :value="status">
          {{ t(`mediaCenter.statuses.${status}`) }}
        </option>
      </select>
      <input
        ref="fileInput"
        type="file"
        accept="image/*"
        class="w-full text-sm"
        @change="form.image = $event.target.files?.[0] || null"
      />
      <div class="flex gap-2">
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white">{{ t('forms.save') }}</button>
        <button type="button" class="rounded border px-4 py-2 text-sm" @click="resetForm">{{ t('forms.cancel') }}</button>
      </div>
    </form>
  </div>
</template>
