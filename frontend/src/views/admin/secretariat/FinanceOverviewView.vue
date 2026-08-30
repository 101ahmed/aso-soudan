<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { pickName } from '@/utils/localized'
import { fetchFinanceOverview, formatEuro, saveFinanceBudget } from '@/services/finance'

const route = useRoute()
const { t, locale } = useI18n()
const auth = useAuthStore()
const code = computed(() => route.params.code)
const year = ref(new Date().getFullYear())
const years = computed(() => {
  const current = new Date().getFullYear()
  return [current + 1, current, current - 1, current - 2]
})

const loading = ref(false)
const saving = ref(false)
const error = ref('')
const overview = ref(null)
const canUpdate = computed(() => auth.hasPermission('finance.update'))

const budgetForm = reactive({
  amount: '',
  notes: '',
})

const kpis = computed(() => {
  const data = overview.value || {}
  return [
    { key: 'balance', emoji: '💵', label: t('financeAdmin.currentBalance'), value: formatEuro(data.current_balance || 0), accent: 'text-teal-800' },
    { key: 'revenues', emoji: '💰', label: t('financeAdmin.totalRevenues'), value: formatEuro(data.total_revenues || 0), accent: 'text-emerald-800' },
    { key: 'expenses', emoji: '💸', label: t('financeAdmin.totalExpenses'), value: formatEuro(data.total_expenses || 0), accent: 'text-rose-800' },
    { key: 'remaining', emoji: '💵', label: t('financeAdmin.remaining'), value: formatEuro(data.current_balance || 0), accent: 'text-slate-800' },
    { key: 'budget', emoji: '📊', label: t('financeAdmin.approvedBudget'), value: formatEuro(data.approved_budget || 0), accent: 'text-slate-800' },
    { key: 'ratio', emoji: '📈', label: t('financeAdmin.spendRatio'), value: data.spend_ratio == null ? '—' : `${data.spend_ratio} %`, accent: 'text-amber-800' },
    { key: 'ops', emoji: '🧾', label: t('financeAdmin.operationsCount'), value: String(data.operations_count || 0), accent: 'text-slate-800' },
  ]
})

async function load() {
  loading.value = true
  error.value = ''
  try {
    overview.value = await fetchFinanceOverview(code.value, { year: year.value })
    budgetForm.amount = overview.value?.budget?.amount ?? ''
    budgetForm.notes = overview.value?.budget?.notes || ''
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    loading.value = false
  }
}

async function saveBudget() {
  if (!canUpdate.value) return
  saving.value = true
  error.value = ''
  try {
    await saveFinanceBudget(code.value, {
      year: year.value,
      amount: Number(budgetForm.amount || 0),
      notes: budgetForm.notes || null,
    })
    await load()
  } catch (e) {
    const errors = e.response?.data?.errors
    error.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || e.message
  } finally {
    saving.value = false
  }
}

function departmentLabel(row) {
  if (!row.department) return t('financeAdmin.unassignedDepartment')
  return pickName(row.department, locale.value) || row.department.code
}

watch(year, load)
onMounted(load)
</script>

<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold">{{ t('financeAdmin.overviewTitle') }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ t('financeAdmin.overviewHint') }}</p>
      </div>
      <label class="text-sm text-slate-600">
        {{ t('financeAdmin.year') }}
        <select v-model.number="year" class="ms-2 rounded border px-3 py-1.5 text-sm">
          <option v-for="item in years" :key="item" :value="item">{{ item }}</option>
        </select>
      </label>
    </div>

    <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>
    <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>

    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
      <article
        v-for="kpi in kpis"
        :key="kpi.key"
        class="rounded-xl border border-slate-200 bg-white p-4"
      >
        <p class="text-sm text-slate-500">{{ kpi.emoji }} {{ kpi.label }}</p>
        <p class="mt-2 text-xl font-semibold" :class="kpi.accent">{{ kpi.value }}</p>
      </article>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
      <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h3 class="font-semibold">{{ t('financeAdmin.revenuesBySource') }}</h3>
        <p v-if="!(overview?.revenues_by_source || []).length" class="mt-3 text-sm text-slate-500">
          {{ t('financeAdmin.emptyRevenues') }}
        </p>
        <ul class="mt-3 space-y-2 text-sm">
          <li
            v-for="row in overview?.revenues_by_source || []"
            :key="row.source"
            class="flex items-center justify-between gap-3 border-b border-slate-100 py-2 last:border-0"
          >
            <span>{{ t(`financeAdmin.sources.${row.source}`) }}</span>
            <span class="font-medium">{{ formatEuro(row.total) }}</span>
          </li>
        </ul>
        <RouterLink
          :to="`/admin/secretariats/${code}/revenues`"
          class="mt-4 inline-flex text-sm font-semibold text-teal-800 hover:underline"
        >
          {{ t('financeAdmin.manageRevenues') }}
        </RouterLink>
      </section>

      <section class="rounded-xl border border-slate-200 bg-white p-5">
        <h3 class="font-semibold">{{ t('financeAdmin.expensesByCategory') }}</h3>
        <p v-if="!(overview?.expenses_by_category || []).length" class="mt-3 text-sm text-slate-500">
          {{ t('financeAdmin.emptyExpenses') }}
        </p>
        <ul class="mt-3 space-y-2 text-sm">
          <li
            v-for="row in overview?.expenses_by_category || []"
            :key="row.category"
            class="flex items-center justify-between gap-3 border-b border-slate-100 py-2 last:border-0"
          >
            <span>{{ t(`financeAdmin.categories.${row.category}`) }}</span>
            <span class="font-medium">{{ formatEuro(row.total) }}</span>
          </li>
        </ul>
        <ul v-if="(overview?.expenses_by_department || []).length" class="mt-4 space-y-2 border-t border-slate-100 pt-3 text-sm">
          <li class="text-xs font-semibold tracking-wide text-slate-500 uppercase">{{ t('financeAdmin.expensesByDepartment') }}</li>
          <li
            v-for="row in overview?.expenses_by_department || []"
            :key="row.department_id || 'none'"
            class="flex items-center justify-between gap-3"
          >
            <span>{{ departmentLabel(row) }}</span>
            <span class="font-medium">{{ formatEuro(row.total) }}</span>
          </li>
        </ul>
        <RouterLink
          :to="`/admin/secretariats/${code}/expenses`"
          class="mt-4 inline-flex text-sm font-semibold text-teal-800 hover:underline"
        >
          {{ t('financeAdmin.manageExpenses') }}
        </RouterLink>
      </section>
    </div>

    <form
      v-if="canUpdate"
      class="max-w-xl space-y-3 rounded-xl border border-slate-200 bg-white p-5"
      @submit.prevent="saveBudget"
    >
      <h3 class="font-semibold">{{ t('financeAdmin.budgetTitle') }} — {{ year }}</h3>
      <p class="text-sm text-slate-600">{{ t('financeAdmin.budgetHint') }}</p>
      <label class="block text-sm">
        <span class="mb-1 block text-slate-600">{{ t('financeAdmin.approvedBudget') }} (€)</span>
        <input v-model="budgetForm.amount" type="number" min="0" step="0.01" required class="w-full rounded border px-3 py-2" />
      </label>
      <textarea v-model="budgetForm.notes" rows="2" class="w-full rounded border px-3 py-2 text-sm" :placeholder="t('financeAdmin.notes')" />
      <button type="submit" class="rounded bg-teal-800 px-4 py-2 text-sm text-white disabled:opacity-60" :disabled="saving">
        {{ saving ? t('admin.saving') : t('forms.save') }}
      </button>
    </form>
  </div>
</template>
