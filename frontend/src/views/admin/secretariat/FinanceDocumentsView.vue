<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  fetchFinanceDocuments,
  publishFinanceDocument,
  unpublishFinanceDocument,
  updateFinanceDocument,
} from '@/services/finance'

const KINDS = ['general_report', 'subscriptions_announcement']

const route = useRoute()
const { t, locale } = useI18n()
const auth = useAuthStore()
const code = computed(() => route.params.code)

const loading = ref(false)
const savingKind = ref('')
const publishingKind = ref('')
const error = ref('')
const notice = ref('')
const items = ref([])
const forms = reactive({
  general_report: emptyForm(),
  subscriptions_announcement: emptyForm(),
})
const files = reactive({
  general_report: null,
  subscriptions_announcement: null,
})

const canUpdate = computed(() => auth.hasPermission('finance.update'))

function emptyForm() {
  return {
    title_ar: '',
    title_fr: '',
    body_ar: '',
    body_fr: '',
  }
}

function labelFor(kind) {
  return kind === 'general_report'
    ? t('financeAdmin.generalReport')
    : t('financeAdmin.subscriptionsAnnouncement')
}

function typeFor(kind) {
  return kind === 'general_report' ? t('financeAdmin.kindReport') : t('financeAdmin.kindAnnouncement')
}

function publicPath(kind) {
  return kind === 'general_report' ? '/secretariats/finance' : '/subscriptions'
}

function titleOf(item) {
  if (!item) return ''
  return locale.value === 'fr' ? (item.title_fr || item.title_ar) : (item.title_ar || item.title_fr)
}

function onFile(kind, event) {
  files[kind] = event.target.files?.[0] || null
}

function fillForms(list) {
  for (const kind of KINDS) {
    const item = list.find((row) => row.kind === kind)
    if (!item) continue
    forms[kind].title_ar = item.title_ar || ''
    forms[kind].title_fr = item.title_fr || ''
    forms[kind].body_ar = item.body_ar || ''
    forms[kind].body_fr = item.body_fr || ''
    files[kind] = null
  }
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    items.value = await fetchFinanceDocuments(code.value)
    fillForms(items.value)
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

function toFormData(kind) {
  const data = new FormData()
  data.append('title_ar', forms[kind].title_ar || '')
  if (forms[kind].title_fr) data.append('title_fr', forms[kind].title_fr)
  if (forms[kind].body_ar) data.append('body_ar', forms[kind].body_ar)
  if (forms[kind].body_fr) data.append('body_fr', forms[kind].body_fr)
  if (files[kind]) data.append('file', files[kind])
  return data
}

function replaceItem(updated) {
  items.value = items.value.map((row) => (row.kind === updated.kind ? updated : row))
  fillForms(items.value)
}

async function save(kind) {
  if (!canUpdate.value) return
  savingKind.value = kind
  error.value = ''
  notice.value = ''
  try {
    const updated = await updateFinanceDocument(code.value, kind, toFormData(kind))
    replaceItem(updated)
    notice.value = t('financeAdmin.saved')
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || e.message
  } finally {
    savingKind.value = ''
  }
}

async function publish(kind) {
  if (!canUpdate.value) return
  publishingKind.value = kind
  error.value = ''
  notice.value = ''
  try {
    const updated = await updateFinanceDocument(code.value, kind, toFormData(kind))
    const published = await publishFinanceDocument(code.value, kind)
    replaceItem({ ...updated, ...published })
    notice.value = t('financeAdmin.publishedNotice')
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || t('financeAdmin.publishNeedContent')
  } finally {
    publishingKind.value = ''
  }
}

async function unpublish(kind) {
  if (!canUpdate.value) return
  publishingKind.value = kind
  error.value = ''
  notice.value = ''
  try {
    const updated = await unpublishFinanceDocument(code.value, kind)
    replaceItem(updated)
    notice.value = t('financeAdmin.unpublishedNotice')
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    publishingKind.value = ''
  }
}

function scrollToHash() {
  const hash = String(route.hash || '').replace('#', '')
  if (!KINDS.includes(hash)) return
  requestAnimationFrame(() => {
    document.getElementById(hash)?.scrollIntoView({ behavior: 'smooth', block: 'start' })
  })
}

watch(() => route.hash, scrollToHash)
onMounted(async () => {
  await load()
  scrollToHash()
})
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold">{{ t('financeAdmin.documentsTitle') }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ t('financeAdmin.documentsHint') }}</p>
      </div>
      <div class="flex flex-wrap gap-2 text-sm">
        <a href="/secretariats/finance" target="_blank" rel="noreferrer" class="rounded border border-teal-800 px-3 py-1.5 text-teal-800 hover:bg-teal-50">
          {{ t('financeAdmin.openPublicFinance') }}
        </a>
        <a href="/subscriptions" target="_blank" rel="noreferrer" class="rounded border border-teal-800 px-3 py-1.5 text-teal-800 hover:bg-teal-50">
          {{ t('financeAdmin.openPublicSubscriptions') }}
        </a>
      </div>
    </div>

    <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>
    <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
    <p v-if="notice" class="rounded border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800">{{ notice }}</p>

    <article
      v-for="kind in KINDS"
      :id="kind"
      :key="kind"
      class="scroll-mt-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm"
    >
      <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
          <h3 class="text-lg font-semibold text-[var(--rdp-forest)]">{{ labelFor(kind) }}</h3>
          <p class="mt-1 text-sm text-slate-500">{{ typeFor(kind) }} · {{ titleOf(items.find((row) => row.kind === kind)) }}</p>
        </div>
        <span
          class="rounded-full px-3 py-1 text-xs font-semibold"
          :class="items.find((row) => row.kind === kind)?.is_published
            ? 'bg-emerald-100 text-emerald-800'
            : 'bg-slate-100 text-slate-600'"
        >
          {{ items.find((row) => row.kind === kind)?.is_published ? t('financeAdmin.published') : t('financeAdmin.draft') }}
        </span>
      </div>

      <p v-if="items.find((row) => row.kind === kind)?.original_name" class="mt-3 text-sm text-slate-600">
        {{ t('financeAdmin.currentFile') }}:
        <span class="font-medium">{{ items.find((row) => row.kind === kind)?.original_name }}</span>
      </p>
      <p v-else class="mt-3 text-sm text-slate-500">{{ t('financeAdmin.noFile') }}</p>

      <div class="mt-4 flex flex-wrap gap-2">
        <a
          v-if="items.find((row) => row.kind === kind)?.file_url"
          :href="items.find((row) => row.kind === kind)?.file_url"
          target="_blank"
          rel="noreferrer"
          class="rounded bg-teal-800 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-900"
        >
          {{ t('financeAdmin.view') }}
        </a>
        <a
          :href="publicPath(kind)"
          target="_blank"
          rel="noreferrer"
          class="rounded border border-slate-300 px-4 py-2 text-sm hover:bg-slate-50"
        >
          {{ t('financeAdmin.publicPage') }}
        </a>
        <button
          v-if="canUpdate && !items.find((row) => row.kind === kind)?.is_published"
          type="button"
          class="rounded bg-emerald-700 px-4 py-2 text-sm font-semibold text-white disabled:opacity-60"
          :disabled="publishingKind === kind || savingKind === kind"
          @click="publish(kind)"
        >
          {{ publishingKind === kind ? t('admin.saving') : t('financeAdmin.publish') }}
        </button>
        <button
          v-if="canUpdate && items.find((row) => row.kind === kind)?.is_published"
          type="button"
          class="rounded border border-amber-400 bg-amber-50 px-4 py-2 text-sm font-semibold text-amber-900 disabled:opacity-60"
          :disabled="publishingKind === kind"
          @click="unpublish(kind)"
        >
          {{ publishingKind === kind ? t('admin.saving') : t('financeAdmin.unpublish') }}
        </button>
      </div>

      <form v-if="canUpdate" class="mt-5 grid gap-3 border-t border-slate-100 pt-4" @submit.prevent="save(kind)">
        <p class="text-sm font-medium text-slate-700">{{ t('financeAdmin.editDocument') }}</p>
        <input v-model="forms[kind].title_ar" required :placeholder="t('financeAdmin.titleAr')" class="w-full rounded border px-3 py-2 text-sm" />
        <input v-model="forms[kind].title_fr" :placeholder="t('financeAdmin.titleFr')" class="w-full rounded border px-3 py-2 text-sm" />
        <textarea v-model="forms[kind].body_ar" rows="4" :placeholder="t('financeAdmin.bodyAr')" class="w-full rounded border px-3 py-2 text-sm" />
        <textarea v-model="forms[kind].body_fr" rows="4" :placeholder="t('financeAdmin.bodyFr')" class="w-full rounded border px-3 py-2 text-sm" />
        <label class="block text-sm text-slate-600">
          {{ files[kind] ? t('financeAdmin.replaceFile') : t('financeAdmin.uploadFile') }}
          <input type="file" class="mt-1 block w-full text-sm" @change="onFile(kind, $event)" />
        </label>
        <button type="submit" class="w-fit rounded bg-teal-800 px-4 py-2 text-sm text-white disabled:opacity-60" :disabled="savingKind === kind">
          {{ savingKind === kind ? t('admin.saving') : t('forms.save') }}
        </button>
      </form>
    </article>
  </div>
</template>
