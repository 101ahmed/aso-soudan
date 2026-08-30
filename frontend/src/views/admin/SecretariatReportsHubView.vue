<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { pickName } from '@/utils/localized'
import { SECRETARIAT_NAME_KEYS } from '@/utils/departmentAccess'
import { fetchSecretariatReportsHub } from '@/services/reports'

const { t, locale } = useI18n()
const year = ref(new Date().getFullYear())
const loading = ref(false)
const error = ref('')
const payload = ref(null)

const years = computed(() => {
  const current = new Date().getFullYear()
  return [current, current - 1, current - 2]
})

async function load() {
  loading.value = true
  error.value = ''
  try {
    payload.value = await fetchSecretariatReportsHub({ year: year.value })
  } catch (e) {
    error.value = e.response?.data?.message || e.message
    payload.value = null
  } finally {
    loading.value = false
  }
}

watch(year, load)
onMounted(load)
</script>

<template>
  <section class="space-y-5">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <h1 class="text-2xl font-semibold text-[var(--rdp-forest)]">📊 {{ t('secretariatReports.hubTitle') }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ t('secretariatReports.hubHint') }}</p>
      </div>
      <select v-model.number="year" class="rounded border px-3 py-2 text-sm">
        <option v-for="item in years" :key="item" :value="item">{{ item }}</option>
      </select>
    </div>

    <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>
    <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>

    <div v-if="payload" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
      <article
        v-for="item in payload.secretariats"
        :key="item.code"
        class="rounded-xl border bg-white p-5"
      >
        <h2 class="font-semibold text-[var(--rdp-forest)]">
          {{ pickName(item, locale) || t(SECRETARIAT_NAME_KEYS[item.code] || item.code) }}
        </h2>
        <p class="mt-2 text-sm text-slate-600">
          {{ t('secretariatReports.news') }}: {{ item.news }}
          · {{ t('secretariatReports.events') }}: {{ item.events }}
          · {{ t('secretariatReports.messages') }}: {{ item.messages }}
        </p>
        <RouterLink
          :to="{ path: `/admin/secretariats/${item.code}/reports`, query: { year: String(year) } }"
          class="mt-4 inline-flex rounded bg-teal-800 px-3 py-1.5 text-sm font-semibold text-white"
        >
          {{ t('secretariatReports.open') }}
        </RouterLink>
      </article>
    </div>
  </section>
</template>
