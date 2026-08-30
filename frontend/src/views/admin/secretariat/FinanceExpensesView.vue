<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { pickName, pickTitle } from '@/utils/localized'
import { fetchPublicDepartments } from '@/services/content'
import {
  EXPENSE_CATEGORIES,
  createFinanceExpense,
  deleteFinanceExpense,
  fetchFinanceExpenses,
  formatEuro,
  updateFinanceExpense,
} from '@/services/finance'
import { isSecretariatCode, SECRETARIAT_NAME_KEYS } from '@/utils/departmentAccess'

const route = useRoute()
const { t, locale } = useI18n()
const auth = useAuthStore()
const code = computed(() => route.params.code)
const items = ref([])
const departments = ref([])
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

const secretariatOptions = computed(() =>
  (departments.value || []).filter((dept) => isSecretariatCode(dept.code)),
)

const filters = reactive({ search: '', category: '', department_id: '' })
const form = reactive(emptyForm())

function emptyForm() {
  return {
    occurred_on: new Date().toISOString().slice(0, 10),
    amount: '',
    category: 'admin',
    department_id: '',
    project_ar: '',
    project_fr: '',
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
  form.category = item.category
  form.department_id = item.department_id || ''
  form.project_ar = item.project_ar || ''
  form.project_fr = item.project_fr || ''
  form.title_ar = item.title_ar
  form.title_fr = item.title_fr
  form.notes = item.notes || ''
}

function departmentLabel(item) {
  if (!item.department) return t('financeAdmin.unassignedDepartment')
  return pickName(item.department, locale.value)
    || t(SECRETARIAT_NAME_KEYS[item.department.code] || item.department.code)
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    const data = await fetchFinanceExpenses(code.value, {
      year: year.value,
      search: filters.search || undefined,
      category: filters.category || undefined,
      department_id: filters.department_id || undefined,
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
      amount: Number(form.amount),
      department_id: form.department_id || null,
    }
    if (editingId.value) await updateFinanceExpense(code.value, editingId.value, payload)
    else await createFinanceExpense(code.value, payload)
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
  await deleteFinanceExpense(code.value, id)
  await load()
}

watch([year, () => filters.search, () => filters.category, () => filters.department_id], load)
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
  <div class="grid gap-6 lg:grid-cols-[1.15fr_1fr]">
    <div class="space-y-3">
      <h2 class="text-lg font-semibold">{{ t('financeAdmin.expensesTitle') }}</h2>
      <p class="text-sm text-slate-600">{{ t('financeAdmin.expensesHint') }}</p>
      <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
        <select v-model.number="year" class="rounded border px-3 py-2 text-sm">
          <option v-for="item in years" :key="item" :value="item">{{ item }}</option>
        </select>
        <select v-model="filters.category" class="rounded border px-3 py-2 text-sm">
          <option value="">{{ t('financeAdmin.allCategories') }}</option>
          <option v-for="category in EXPENSE_CATEGORIES" :key="category" :value="category">
            {{ t(`financeAdmin.categories.${category}`) }}
          </option>
        </select>
        <select v-model="filters.department_id" class="rounded border px-3 py-2 text-sm">
          <option value="">{{ t('financeAdmin.allDepartments') }}</option>
          <option v-for="dept in secretariatOptions" :key="dept.id" :value="String(dept.id)">
            {{ pickName(dept, locale) }}
          </option>
        </select>
        <input v-model="filters.search" :placeholder="t('financeAdmin.search')" class="rounded border px-3 py-2 text-sm" />
      </div>
      <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>
      <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>
      <p v-else-if="!loading && !items.length" class="text-sm text-slate-500">{{ t('financeAdmin.emptyExpenses') }}</p>
      <article v-for="item in items" :key="item.id" class="rounded-lg border border-slate-200 bg-white p-4">
        <div class="flex items-start justify-between gap-3">
          <div>
            <p class="font-medium">{{ pickTitle(item, locale) }}</p>
            <p class="mt-1 text-sm text-slate-600">
              {{ item.occurred_on }} · {{ t(`financeAdmin.categories.${item.category}`) }} · {{ departmentLabel(item) }}
            </p>
            <p v-if="item.project_ar || item.project_fr" class="mt-1 text-xs text-slate-500">
              {{ t('financeAdmin.project') }}:
              {{ locale.startsWith('ar') ? (item.project_ar || item.project_fr) : (item.project_fr || item.project_ar) }}
            </p>
            <p class="mt-1 font-semibold text-rose-800">{{ formatEuro(item.amount) }}</p>
          </div>
          <div class="flex gap-1">
            <button v-if="canUpdate" type="button" class="rounded border px-2 py-1 text-xs" @click="edit(item)">{{ t('forms.edit') }}</button>
            <button v-if="canDelete" type="button" class="rounded border border-rose-300 px-2 py-1 text-xs text-rose-700" @click="remove(item.id)">{{ t('forms.delete') }}</button>
          </div>
        </div>
      </article>
    </div>

    <form v-if="canManage" class="space-y-3 rounded-xl border bg-white p-5" @submit.prevent="save">
      <h3 class="font-semibold">{{ editingId ? t('financeAdmin.editExpense') : t('financeAdmin.newExpense') }}</h3>
      <input v-model="form.occurred_on" type="date" required class="w-full rounded border px-3 py-2 text-sm" />
      <input v-model="form.amount" type="number" min="0.01" step="0.01" required class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('financeAdmin.amount')" />
      <select v-model="form.category" class="w-full rounded border px-3 py-2 text-sm">
        <option v-for="category in EXPENSE_CATEGORIES" :key="category" :value="category">{{ t(`financeAdmin.categories.${category}`) }}</option>
      </select>
      <select v-model="form.department_id" class="w-full rounded border px-3 py-2 text-sm">
        <option value="">{{ t('financeAdmin.unassignedDepartment') }}</option>
        <option v-for="dept in secretariatOptions" :key="dept.id" :value="dept.id">{{ pickName(dept, locale) }}</option>
      </select>
      <input v-model="form.project_fr" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('financeAdmin.projectFr')" />
      <input v-model="form.project_ar" dir="rtl" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('financeAdmin.projectAr')" />
      <input v-model="form.title_fr" required class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.titleFr')" />
      <input v-model="form.title_ar" required dir="rtl" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('secretariatAdmin.titleAr')" />
      <textarea v-model="form.notes" rows="3" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('financeAdmin.notes')" />
      <p class="text-xs text-slate-500">{{ t('financeAdmin.expenseLinkHint') }}</p>
      <div class="flex gap-2">
        <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white">{{ t('forms.save') }}</button>
        <button type="button" class="rounded border px-4 py-2 text-sm" @click="resetForm">{{ t('forms.cancel') }}</button>
      </div>
    </form>
  </div>
</template>
