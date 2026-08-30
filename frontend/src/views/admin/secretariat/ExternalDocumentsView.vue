<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  createExternalDocument,
  deleteExternalDocument,
  fetchExternalDocuments,
  fetchPartners,
  updateExternalDocument,
} from '@/services/external'

const route = useRoute()
const { t, locale } = useI18n()
const auth = useAuthStore()
const code = computed(() => route.params.code)

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const items = ref([])
const partners = ref([])
const editingId = ref(null)
const categories = ['agreement', 'letter', 'report', 'representation', 'other']

const filters = reactive({ search: '', category: '', partner_id: '' })
const form = reactive(emptyForm())

const canCreate = computed(() => auth.hasPermission('partner.create'))
const canUpdate = computed(() => auth.hasPermission('partner.update'))
const canDelete = computed(() => auth.hasPermission('partner.delete'))
const canManage = computed(() => (editingId.value ? canUpdate.value : canCreate.value))

function emptyForm() {
  return {
    title_ar: '',
    title_fr: '',
    category: 'agreement',
    partner_id: '',
    is_public: false,
    file: null,
  }
}

function resetForm() {
  editingId.value = null
  Object.assign(form, emptyForm())
}

function partnerName(item) {
  const p = item.partner
  if (!p) return ''
  return locale.value === 'fr' ? (p.name_fr || p.name_ar) : (p.name_ar || p.name_fr)
}

function edit(item) {
  editingId.value = item.id
  form.title_ar = item.title_ar || ''
  form.title_fr = item.title_fr || ''
  form.category = item.category || 'other'
  form.partner_id = item.partner_id || ''
  form.is_public = !!item.is_public
  form.file = null
}

function onFile(e) {
  form.file = e.target.files?.[0] || null
}

function toFormData() {
  const data = new FormData()
  data.append('title_ar', form.title_ar)
  if (form.title_fr) data.append('title_fr', form.title_fr)
  data.append('category', form.category)
  if (form.partner_id) data.append('partner_id', String(form.partner_id))
  data.append('is_public', form.is_public ? '1' : '0')
  if (form.file) data.append('file', form.file)
  return data
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const [docs, orgs] = await Promise.all([
      fetchExternalDocuments(code.value, {
        search: filters.search || undefined,
        category: filters.category || undefined,
        partner_id: filters.partner_id || undefined,
      }),
      fetchPartners(code.value, { per_page: 100 }),
    ])
    items.value = docs.data || []
    partners.value = orgs.data || []
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

async function save() {
  if (!canManage.value) return
  if (!editingId.value && !form.file) {
    error.value = t('externalRel.fileRequired')
    return
  }
  saving.value = true
  error.value = ''
  try {
    if (editingId.value) {
      await updateExternalDocument(code.value, editingId.value, toFormData())
    } else {
      await createExternalDocument(code.value, toFormData())
    }
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
  await deleteExternalDocument(code.value, id)
  await load()
}

watch([() => filters.search, () => filters.category, () => filters.partner_id], load)
onMounted(load)
</script>

<template>
  <div class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
    <div class="space-y-3">
      <h2 class="text-lg font-semibold">{{ t('externalRel.filesTitle') }}</h2>
      <p class="text-sm text-slate-600">{{ t('externalRel.filesHint') }}</p>
      <div class="grid gap-2 sm:grid-cols-3">
        <input v-model="filters.search" :placeholder="t('externalRel.searchFiles')" class="rounded border px-3 py-2 text-sm" />
        <select v-model="filters.category" class="rounded border px-3 py-2 text-sm">
          <option value="">{{ t('externalRel.allCategories') }}</option>
          <option v-for="cat in categories" :key="cat" :value="cat">{{ t(`externalRel.categories.${cat}`) }}</option>
        </select>
        <select v-model="filters.partner_id" class="rounded border px-3 py-2 text-sm">
          <option value="">{{ t('externalRel.allPartners') }}</option>
          <option v-for="p in partners" :key="p.id" :value="p.id">{{ p.name_ar }}</option>
        </select>
      </div>
      <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>
      <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>
      <article v-for="item in items" :key="item.id" class="rounded-lg border border-slate-200 bg-white p-4">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p class="font-medium">{{ item.title_ar }}</p>
            <p class="mt-1 text-sm text-slate-600">
              {{ t(`externalRel.categories.${item.category}`) }}
              <span v-if="partnerName(item)"> · {{ partnerName(item) }}</span>
            </p>
            <a
              v-if="item.file_url"
              :href="item.file_url"
              target="_blank"
              rel="noreferrer"
              class="mt-2 inline-flex text-sm font-semibold text-teal-800 hover:underline"
            >
              {{ t('externalRel.download') }}
            </a>
          </div>
          <div class="flex flex-col items-end gap-2">
            <span v-if="item.is_public" class="text-xs text-emerald-700">{{ t('externalRel.public') }}</span>
            <div class="flex gap-1">
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
          </div>
        </div>
      </article>
    </div>

    <form class="space-y-3 rounded-xl border border-slate-200 bg-white p-5" @submit.prevent="save">
      <h3 class="font-semibold">{{ editingId ? t('externalRel.editFile') : t('externalRel.newFile') }}</h3>
      <input v-model="form.title_ar" required :placeholder="t('externalRel.titleAr')" class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.title_fr" :placeholder="t('externalRel.titleFr')" class="w-full rounded border px-3 py-2 text-sm" />
      <select v-model="form.category" class="w-full rounded border px-3 py-2 text-sm">
        <option v-for="cat in categories" :key="cat" :value="cat">{{ t(`externalRel.categories.${cat}`) }}</option>
      </select>
      <select v-model="form.partner_id" class="w-full rounded border px-3 py-2 text-sm">
        <option value="">{{ t('externalRel.noPartner') }}</option>
        <option v-for="p in partners" :key="p.id" :value="p.id">{{ p.name_ar }}</option>
      </select>
      <input type="file" class="w-full text-sm" @change="onFile" />
      <label class="flex items-center gap-2 text-sm">
        <input v-model="form.is_public" type="checkbox" />
        {{ t('externalRel.showPublic') }}
      </label>
      <div class="flex gap-2">
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white disabled:opacity-50" :disabled="!canManage || saving">
          {{ t('forms.save') }}
        </button>
        <button type="button" class="rounded border px-4 py-2 text-sm" @click="resetForm">{{ t('forms.cancel') }}</button>
      </div>
    </form>
  </div>
</template>
