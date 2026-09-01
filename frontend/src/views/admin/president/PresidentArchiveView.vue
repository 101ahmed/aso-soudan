<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { pickTitle } from '@/utils/localized'
import {
  createPresidentArchiveItem,
  deletePresidentArchiveItem,
  fetchPresidentArchive,
} from '@/services/president'

const { t, locale } = useI18n()
const items = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')

const categories = ['decision', 'directive', 'agreement', 'minutes', 'correspondence', 'report']

const filters = reactive({
  category: '',
  decision_number: '',
  date_from: '',
  date_to: '',
  search: '',
})

const form = reactive({
  category: 'agreement',
  title_ar: '',
  title_fr: '',
  body_ar: '',
  body_fr: '',
  decision_number: '',
  document_date: '',
})

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await fetchPresidentArchive({
      category: filters.category || undefined,
      decision_number: filters.decision_number || undefined,
      date_from: filters.date_from || undefined,
      date_to: filters.date_to || undefined,
      search: filters.search || undefined,
    })
    items.value = data.data || []
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

async function save() {
  saving.value = true
  error.value = ''
  try {
    await createPresidentArchiveItem({
      ...form,
      document_date: form.document_date || null,
      decision_number: form.decision_number || null,
    })
    form.title_ar = ''
    form.title_fr = ''
    form.body_ar = ''
    form.body_fr = ''
    form.decision_number = ''
    form.document_date = ''
    await load()
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || e.message
  } finally {
    saving.value = false
  }
}

async function remove(id) {
  if (!confirm(t('presidentAdmin.confirmDelete'))) return
  await deletePresidentArchiveItem(id)
  await load()
}

watch(filters, load, { deep: true })
onMounted(load)
</script>

<template>
  <div class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
    <div class="space-y-3">
      <h2 class="text-lg font-semibold">{{ t('presidentAdmin.archive') }} 🗄️</h2>
      <p class="text-sm text-slate-600">{{ t('presidentAdmin.archiveHint') }}</p>

      <div class="grid gap-2 sm:grid-cols-2">
        <select v-model="filters.category" class="rounded border px-3 py-2 text-sm">
          <option value="">{{ t('presidentAdmin.allCategories') }}</option>
          <option v-for="cat in categories" :key="cat" :value="cat">{{ t(`presidentAdmin.categories.${cat}`) }}</option>
        </select>
        <input v-model="filters.decision_number" class="rounded border px-3 py-2 text-sm" :placeholder="t('presidentAdmin.searchDecisionNumber')" />
        <input v-model="filters.date_from" type="date" class="rounded border px-3 py-2 text-sm" />
        <input v-model="filters.date_to" type="date" class="rounded border px-3 py-2 text-sm" />
        <input v-model="filters.search" class="rounded border px-3 py-2 text-sm sm:col-span-2" :placeholder="t('presidentAdmin.searchArchive')" />
      </div>

      <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>
      <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>

      <div class="overflow-x-auto rounded-xl border bg-white">
        <table class="min-w-full text-sm">
          <thead class="bg-slate-50 text-start text-slate-600">
            <tr>
              <th class="px-3 py-2 font-medium">{{ t('presidentAdmin.documentDate') }}</th>
              <th class="px-3 py-2 font-medium">{{ t('presidentAdmin.decisionNumber') }}</th>
              <th class="px-3 py-2 font-medium">{{ t('presidentAdmin.category') }}</th>
              <th class="px-3 py-2 font-medium">{{ t('presidentAdmin.archiveTitle') }}</th>
              <th class="px-3 py-2" />
            </tr>
          </thead>
          <tbody>
            <tr v-for="item in items" :key="item.id" class="border-t align-top">
              <td class="px-3 py-2 whitespace-nowrap text-slate-500">{{ item.document_date || '—' }}</td>
              <td class="px-3 py-2 font-medium">{{ item.decision_number || '—' }}</td>
              <td class="px-3 py-2">{{ t(`presidentAdmin.categories.${item.category}`) }}</td>
              <td class="px-3 py-2">
                <p>{{ pickTitle(item, locale) }}</p>
                <p class="mt-1 whitespace-pre-line text-xs text-slate-600">
                  {{ locale === 'ar' ? (item.body_ar || item.body_fr) : (item.body_fr || item.body_ar) }}
                </p>
              </td>
              <td class="px-3 py-2">
                <button
                  v-if="item.source_type === 'manual'"
                  type="button"
                  class="text-xs text-rose-700 hover:underline"
                  @click="remove(item.id)"
                >
                  {{ t('forms.delete') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        <p v-if="!loading && !items.length" class="px-3 py-4 text-sm text-slate-500">{{ t('presidentAdmin.emptyArchive') }}</p>
      </div>
    </div>

    <form class="space-y-3 rounded-xl border bg-white p-5" @submit.prevent="save">
      <h3 class="font-semibold">{{ t('presidentAdmin.addArchive') }}</h3>
      <p class="text-sm text-slate-600">{{ t('presidentAdmin.addArchiveHint') }}</p>
      <select v-model="form.category" class="w-full rounded border px-3 py-2 text-sm">
        <option v-for="cat in categories" :key="cat" :value="cat">{{ t(`presidentAdmin.categories.${cat}`) }}</option>
      </select>
      <input v-model="form.title_ar" required class="w-full rounded border px-3 py-2 text-sm" dir="rtl" :placeholder="t('presidentAdmin.titleAr')" />
      <input v-model="form.title_fr" required class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('presidentAdmin.titleFr')" />
      <input v-model="form.decision_number" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('presidentAdmin.decisionNumber')" />
      <input v-model="form.document_date" type="date" class="w-full rounded border px-3 py-2 text-sm" />
      <textarea v-model="form.body_ar" rows="4" class="w-full rounded border px-3 py-2 text-sm" dir="rtl" :placeholder="t('presidentAdmin.bodyAr')" />
      <textarea v-model="form.body_fr" rows="3" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('presidentAdmin.bodyFr')" />
      <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white disabled:opacity-50" :disabled="saving">
        {{ t('forms.save') }}
      </button>
    </form>
  </div>
</template>
