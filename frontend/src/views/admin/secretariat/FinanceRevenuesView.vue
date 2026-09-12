<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { pickTitle } from '@/utils/localized'
import {
  REVENUE_SOURCES,
  createFinanceRevenue,
  deleteFinanceRevenue,
  fetchFinanceRevenues,
  formatEuro,
  updateFinanceRevenue,
} from '@/services/finance'

const route = useRoute()
const { t, locale } = useI18n()
const auth = useAuthStore()
const code = computed(() => route.params.code)
const items = ref([])
const loading = ref(false)
const error = ref('')
const editingId = ref(null)
const year = ref(new Date().getFullYear())
const years = computed(() => {
  const current = new Date().getFullYear()
  return [current + 1, current, current - 1, current - 2]
})

const canCreate = computed(() => auth.hasPermission('finance.create'))
const canUpdate = computed(() => auth.hasPermission('finance.update'))
const canDelete = computed(() => auth.hasPermission('finance.delete'))
const canManage = computed(() => (editingId.value ? canUpdate.value : canCreate.value))

const filters = reactive({ search: '', source: 'membership' })
const form = reactive(emptyForm())
const subscriptionsTotal = ref(0)

function emptyForm() {
  return {
    occurred_on: new Date().toISOString().slice(0, 10),
    amount: '',
    source: 'membership',
    title_ar: '',
    title_fr: '',
    notes: '',
  }
}

function resetForm() {
  editingId.value = null
  Object.assign(form, emptyForm())
}

function edit(item) {
  editingId.value = item.id
  form.occurred_on = item.occurred_on
  form.amount = item.amount
  form.source = item.source
  form.title_ar = item.title_ar
  form.title_fr = item.title_fr
  form.notes = item.notes || ''
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await fetchFinanceRevenues(code.value, {
      year: year.value,
      search: filters.search || undefined,
      source: filters.source || undefined,
    })
    items.value = data.data || []
    subscriptionsTotal.value = Number(data.subscriptions_total || 0)
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
    const payload = { ...form, amount: Number(form.amount) }
    if (editingId.value) await updateFinanceRevenue(code.value, editingId.value, payload)
    else await createFinanceRevenue(code.value, payload)
    resetForm()
    await load()
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || e.message
  }
}

async function remove(id) {
  if (!canDelete.value) return
  if (!confirm(t('secretariatAdmin.confirmDelete'))) return
  await deleteFinanceRevenue(code.value, id)
  await load()
}

watch([year, () => filters.search, () => filters.source], load)
onMounted(load)
</script>

<template>
  <div class="grid gap-6 lg:grid-cols-[1.15fr_1fr]">
    <div class="space-y-3">
      <h2 class="text-lg font-semibold">{{ t('financeAdmin.revenuesTitle') }}</h2>
      <p class="text-sm text-slate-600">{{ t('financeAdmin.revenuesHint') }}</p>
      <p class="rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
        {{ t('financeAdmin.subscriptionsTotal') }}:
        <span class="font-semibold">{{ formatEuro(subscriptionsTotal) }}</span>
        <RouterLink
          :to="`/admin/secretariats/${code}/members`"
          class="ms-2 font-semibold underline"
        >
          {{ t('financeAdmin.openSubscriptions') }}
        </RouterLink>
      </p>
      <div class="grid gap-2 sm:grid-cols-3">
        <select v-model.number="year" class="rounded border px-3 py-2 text-sm">
          <option v-for="item in years" :key="item" :value="item">{{ item }}</option>
        </select>
        <select v-model="filters.source" class="rounded border px-3 py-2 text-sm">
          <option value="">{{ t('financeAdmin.allSources') }}</option>
          <option v-for="source in REVENUE_SOURCES" :key="source" :value="source">{{ t(`financeAdmin.sources.${source}`) }}</option>
        </select>
        <input v-model="filters.search" :placeholder="t('financeAdmin.search')" class="rounded border px-3 py-2 text-sm" />
      </div>
      <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>
      <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>
      <p v-else-if="!loading && !items.length" class="text-sm text-slate-500">{{ t('financeAdmin.emptyRevenues') }}</p>
      <article v-for="item in items" :key="item.id" class="rounded-lg border border-slate-200 bg-white p-4">
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="font-medium">{{ item.member?.full_name || pickTitle(item, locale) }}</p>
            <p class="mt-1 text-sm text-slate-600">
              {{ item.occurred_on }} · {{ t(`financeAdmin.sources.${item.source}`) }}
              <span v-if="item.from_subscription"> · {{ t('financeAdmin.fromSubscription') }}</span>
            </p>
            <p v-if="item.member?.subscription_status" class="mt-1 text-xs text-slate-500">
              {{ t(`statisticsMembers.dues.${item.member.subscription_status}`) }}
            </p>
            <p class="mt-1 font-semibold text-emerald-800">{{ formatEuro(item.amount) }}</p>
          </div>
          <div v-if="!item.from_subscription" class="flex gap-1">
            <button v-if="canUpdate" type="button" class="rounded border px-2 py-1 text-xs" @click="edit(item)">{{ t('forms.edit') }}</button>
            <button v-if="canDelete" type="button" class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700" @click="remove(item.id)">{{ t('forms.delete') }}</button>
          </div>
        </div>
      </article>
    </div>

    <form v-if="canManage" class="space-y-3 rounded-xl border bg-white p-5" @submit.prevent="save">
      <h3 class="font-semibold">{{ editingId ? t('financeAdmin.editRevenue') : t('financeAdmin.newRevenue') }}</h3>
      <input v-model="form.occurred_on" type="date" required class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.amount" type="number" min="0.01" step="0.01" required class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('financeAdmin.amount')" />
      <select v-model="form.source" class="w-full rounded border px-3 py-2 text-sm">
        <option v-for="source in REVENUE_SOURCES" :key="source" :value="source">{{ t(`financeAdmin.sources.${source}`) }}</option>
      </select>
      <input v-model="form.title_fr" required class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.titleFr')" />
      <input v-model="form.title_ar" required dir="rtl" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.titleAr')" />
      <textarea v-model="form.notes" rows="3" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('financeAdmin.notes')" />
      <div class="flex gap-2">
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white">{{ t('forms.save') }}</button>
        <button type="button" class="rounded border px-4 py-2 text-sm" @click="resetForm">{{ t('forms.cancel') }}</button>
      </div>
    </form>
  </div>
</template>
