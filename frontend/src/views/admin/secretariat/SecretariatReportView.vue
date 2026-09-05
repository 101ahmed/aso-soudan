<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { pickName, pickTitle } from '@/utils/localized'
import { downloadSecretariatReportPdf, fetchSecretariatReport } from '@/services/reports'

const route = useRoute()
const { t, locale, te } = useI18n()
const auth = useAuthStore()
const code = computed(() => route.params.code)
const year = ref(Number(route.query.year) || new Date().getFullYear())
const loading = ref(false)
const downloading = ref(false)
const error = ref('')
const report = ref(null)

const canExport = computed(
  () =>
    auth.hasPermission('report.export') ||
    auth.user?.roles?.some((r) => ['SUPER_ADMIN', 'PRESIDENT', 'VICE_PRESIDENT'].includes(r.code)),
)

const years = computed(() => {
  const current = new Date().getFullYear()
  return [current, current - 1, current - 2]
})

const deptName = computed(() => (report.value ? pickName(report.value.department, locale.value) : ''))
const officer = computed(() =>
  report.value ? pickName({
    name_ar: report.value.department.officer_name_ar,
    name_fr: report.value.department.officer_name_fr,
  }, locale.value) : '',
)
const deputy = computed(() =>
  report.value ? pickName({
    name_ar: report.value.department.deputy_name_ar,
    name_fr: report.value.department.deputy_name_fr,
  }, locale.value) : '',
)

function keyLabel(key) {
  const path = `secretariatReports.keys.${key}`
  return te(path) ? t(path) : key
}

function money(value) {
  return new Intl.NumberFormat(locale.value === 'ar' ? 'fr-FR' : locale.value, {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  }).format(Number(value || 0))
}

async function load() {
  loading.value = true
  error.value = ''
  try {
    report.value = await fetchSecretariatReport(code.value, { year: year.value })
  } catch (e) {
    error.value = e.response?.data?.message || e.message
    report.value = null
  } finally {
    loading.value = false
  }
}

function printReport() {
  const previous = document.title
  document.title = `${t('secretariatReports.title')} — ${deptName.value} — ${year.value}`
  window.print()
  document.title = previous
}

async function downloadPdf() {
  if (!canExport.value) {
    printReport()
    return
  }
  downloading.value = true
  error.value = ''
  try {
    const { blob, contentType } = await downloadSecretariatReportPdf(code.value, {
      year: year.value,
      locale: locale.value,
    })
    if (blob.type.includes('json') || String(contentType).includes('json')) {
      const payload = JSON.parse(await blob.text())
      throw new Error(payload.message || t('secretariatReports.downloadFailed'))
    }
    const url = URL.createObjectURL(blob)
    const isPdf = String(contentType).includes('pdf')
    const a = document.createElement('a')
    a.href = url
    a.download = `rapport-${code.value}-${year.value}.${isPdf ? 'pdf' : 'html'}`
    document.body.appendChild(a)
    a.click()
    a.remove()
    setTimeout(() => URL.revokeObjectURL(url), 1500)
  } catch (e) {
    const data = e.response?.data
    if (data instanceof Blob) {
      try {
        const payload = JSON.parse(await data.text())
        error.value = payload.message || t('secretariatReports.downloadFailed')
      } catch {
        error.value = t('secretariatReports.downloadFailed')
      }
    } else {
      error.value = e.message || e.response?.data?.message || t('secretariatReports.downloadFailed')
    }
  } finally {
    downloading.value = false
  }
}

watch([code, year], load)
onMounted(load)
</script>

<template>
  <div class="space-y-4">
    <div class="no-print flex flex-wrap items-start justify-between gap-3">
      <div>
        <h2 class="text-lg font-semibold">📊 {{ t('secretariatReports.title') }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ t('secretariatReports.hint') }}</p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        <select v-model.number="year" class="rounded border px-3 py-2 text-sm">
          <option v-for="item in years" :key="item" :value="item">{{ item }}</option>
        </select>
        <button type="button" class="rounded border px-3 py-2 text-sm" @click="printReport">
          🖨️ {{ t('secretariatReports.print') }}
        </button>
        <button
          type="button"
          class="rounded bg-teal-800 px-3 py-2 text-sm font-semibold text-white disabled:opacity-60"
          :disabled="downloading || !report"
          @click="downloadPdf"
        >
          ⬇️ {{ downloading ? t('secretariatReports.downloading') : t('secretariatReports.downloadPdf') }}
        </button>
      </div>
    </div>
    <p class="no-print text-xs text-slate-500">{{ t('secretariatReports.pdfHint') }}</p>

    <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>
    <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>

    <article v-if="report" id="report-sheet" class="space-y-6 rounded-xl border bg-white p-6">
      <header class="border-b pb-4">
        <p class="text-xs text-slate-500">{{ t('app.name') }}</p>
        <h3 class="mt-1 text-xl font-semibold text-[var(--rdp-forest)]">
          {{ t('secretariatReports.title') }} — {{ deptName }}
        </h3>
        <p class="mt-1 text-sm text-slate-600">
          {{ t('secretariatReports.year') }} {{ report.year }}
          · {{ t('secretariatReports.generated') }}
          {{ new Date(report.generated_at).toLocaleString() }}
        </p>
        <p v-if="officer || deputy" class="mt-2 text-sm">
          <span v-if="officer">👤 {{ t('secretariatReports.officer') }}: <strong>{{ officer }}</strong></span>
          <span v-if="officer && deputy"> · </span>
          <span v-if="deputy">{{ t('secretariatReports.deputy') }}: <strong>{{ deputy }}</strong></span>
        </p>
      </header>

      <section>
        <h4 class="mb-3 font-semibold text-[var(--rdp-forest)]">{{ t('secretariatReports.activity') }}</h4>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <div class="rounded-lg border p-3">
            <p class="text-xs text-slate-500">{{ t('secretariatReports.news') }}</p>
            <p class="text-xl font-semibold">{{ report.activity.news_total }}</p>
            <p class="text-xs text-slate-500">{{ report.activity.news_published }} {{ t('secretariatReports.published') }}</p>
          </div>
          <div class="rounded-lg border p-3">
            <p class="text-xs text-slate-500">{{ t('secretariatReports.events') }}</p>
            <p class="text-xl font-semibold">{{ report.activity.events_total }}</p>
            <p class="text-xs text-slate-500">{{ report.activity.events_published }} {{ t('secretariatReports.published') }}</p>
          </div>
          <div class="rounded-lg border p-3">
            <p class="text-xs text-slate-500">{{ t('secretariatReports.announcements') }}</p>
            <p class="text-xl font-semibold">{{ report.activity.announcements_total }}</p>
          </div>
          <div class="rounded-lg border p-3">
            <p class="text-xs text-slate-500">{{ t('secretariatReports.messages') }}</p>
            <p class="text-xl font-semibold">{{ report.activity.messages_total }}</p>
            <p class="text-xs text-slate-500">{{ report.activity.messages_new }} {{ t('secretariatReports.newItems') }}</p>
          </div>
        </div>
      </section>

      <section v-if="report.specific?.kind === 'academic'">
        <h4 class="mb-3 font-semibold text-[var(--rdp-forest)]">{{ t('secretariatReports.academic') }}</h4>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <div class="rounded-lg border p-3">
            <p class="text-xs text-slate-500">{{ t('secretariatReports.students') }}</p>
            <p class="text-xl font-semibold">{{ report.specific.students_active }} / {{ report.specific.students_total }}</p>
          </div>
          <div class="rounded-lg border p-3">
            <p class="text-xs text-slate-500">{{ t('secretariatReports.teachers') }}</p>
            <p class="text-xl font-semibold">{{ report.specific.teachers_active }}</p>
          </div>
          <div class="rounded-lg border p-3">
            <p class="text-xs text-slate-500">{{ t('secretariatReports.attendanceRate') }}</p>
            <p class="text-xl font-semibold">{{ report.specific.attendance_rate != null ? `${report.specific.attendance_rate}%` : '—' }}</p>
          </div>
          <div class="rounded-lg border p-3 text-sm">
            <p>{{ t('secretariatReports.present') }}: {{ report.specific.attendance_present }}</p>
            <p>{{ t('secretariatReports.absent') }}: {{ report.specific.attendance_absent }}</p>
          </div>
        </div>
      </section>

      <section v-else-if="report.specific?.kind === 'social'">
        <h4 class="mb-3 font-semibold text-[var(--rdp-forest)]">{{ t('secretariatReports.social') }}</h4>
        <p class="mb-3 text-2xl font-semibold">{{ report.specific.total }}</p>
        <div class="grid gap-4 md:grid-cols-2">
          <table class="w-full text-sm">
            <thead><tr class="border-b text-start text-xs text-slate-500"><th class="py-2">{{ t('secretariatReports.status') }}</th><th>{{ t('secretariatReports.count') }}</th></tr></thead>
            <tbody>
              <tr v-for="row in report.specific.by_status" :key="row.key" class="border-b">
                <td class="py-2">{{ keyLabel(row.key) }}</td>
                <td>{{ row.total }}</td>
              </tr>
            </tbody>
          </table>
          <table class="w-full text-sm">
            <thead><tr class="border-b text-start text-xs text-slate-500"><th class="py-2">{{ t('secretariatReports.type') }}</th><th>{{ t('secretariatReports.count') }}</th></tr></thead>
            <tbody>
              <tr v-for="row in report.specific.by_type" :key="row.key" class="border-b">
                <td class="py-2">{{ keyLabel(row.key) }}</td>
                <td>{{ row.total }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </section>

      <section v-else-if="report.specific?.kind === 'finance'">
        <h4 class="mb-3 font-semibold text-[var(--rdp-forest)]">{{ t('secretariatReports.finance') }}</h4>
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
          <div class="rounded-lg border p-3">
            <p class="text-xs text-slate-500">{{ t('secretariatReports.budget') }}</p>
            <p class="text-lg font-semibold">{{ money(report.specific.approved_budget) }}</p>
          </div>
          <div class="rounded-lg border p-3">
            <p class="text-xs text-slate-500">{{ t('secretariatReports.revenues') }}</p>
            <p class="text-lg font-semibold text-emerald-700">{{ money(report.specific.total_revenues) }}</p>
          </div>
          <div class="rounded-lg border p-3">
            <p class="text-xs text-slate-500">{{ t('secretariatReports.expenses') }}</p>
            <p class="text-lg font-semibold text-rose-700">{{ money(report.specific.total_expenses) }}</p>
          </div>
          <div class="rounded-lg border p-3">
            <p class="text-xs text-slate-500">{{ t('secretariatReports.balance') }}</p>
            <p class="text-lg font-semibold">{{ money(report.specific.balance) }}</p>
          </div>
          <div class="rounded-lg border p-3">
            <p class="text-xs text-slate-500">{{ t('secretariatReports.remaining') }}</p>
            <p class="text-lg font-semibold">{{ money(report.specific.remaining_budget) }}</p>
          </div>
        </div>
      </section>

      <section v-else-if="report.specific?.kind === 'media'">
        <h4 class="mb-3 font-semibold text-[var(--rdp-forest)]">{{ t('secretariatReports.media') }}</h4>
        <div class="grid gap-3 sm:grid-cols-3">
          <div class="rounded-lg border p-3">
            <p class="text-xs text-slate-500">{{ t('secretariatReports.decisions') }}</p>
            <p class="text-xl font-semibold">{{ report.specific.decisions_total }}</p>
          </div>
          <div class="rounded-lg border p-3">
            <p class="text-xs text-slate-500">{{ t('secretariatReports.press') }}</p>
            <p class="text-xl font-semibold">{{ report.specific.press_total }}</p>
          </div>
          <div class="rounded-lg border p-3">
            <p class="text-xs text-slate-500">{{ t('secretariatReports.pressPublished') }}</p>
            <p class="text-xl font-semibold">{{ report.specific.press_published }}</p>
          </div>
        </div>
      </section>

      <section v-else-if="report.specific?.kind === 'statistics'">
        <h4 class="mb-3 font-semibold text-[var(--rdp-forest)]">{{ t('secretariatReports.members') }}</h4>
        <p class="text-2xl font-semibold">{{ report.specific.members_total }}</p>
      </section>

      <section v-else-if="report.specific?.kind === 'external'">
        <h4 class="mb-3 font-semibold text-[var(--rdp-forest)]">{{ t('secretariatReports.external') }}</h4>
        <div class="grid gap-3 sm:grid-cols-3">
          <div class="rounded-lg border p-3">
            <p class="text-xs text-slate-500">{{ t('secretariatReports.partners') }}</p>
            <p class="text-xl font-semibold">{{ report.specific.partners_total }}</p>
          </div>
          <div class="rounded-lg border p-3">
            <p class="text-xs text-slate-500">{{ t('secretariatReports.documents') }}</p>
            <p class="text-xl font-semibold">{{ report.specific.documents_total }}</p>
          </div>
          <div class="rounded-lg border p-3">
            <p class="text-xs text-slate-500">{{ t('secretariatReports.contacts') }}</p>
            <p class="text-xl font-semibold">{{ report.specific.contacts_total }}</p>
          </div>
        </div>
      </section>

      <section class="grid gap-6 md:grid-cols-2">
        <div>
          <h4 class="mb-2 font-semibold text-[var(--rdp-forest)]">{{ t('secretariatReports.recentNews') }}</h4>
          <ul v-if="report.activity.recent_news.length" class="space-y-1 text-sm">
            <li v-for="(item, index) in report.activity.recent_news" :key="index">
              {{ pickTitle(item, locale) }} <span class="text-slate-500">— {{ item.date || '—' }}</span>
            </li>
          </ul>
          <p v-else class="text-sm text-slate-500">{{ t('secretariatReports.empty') }}</p>
        </div>
        <div>
          <h4 class="mb-2 font-semibold text-[var(--rdp-forest)]">{{ t('secretariatReports.recentEvents') }}</h4>
          <ul v-if="report.activity.recent_events.length" class="space-y-1 text-sm">
            <li v-for="(item, index) in report.activity.recent_events" :key="index">
              {{ pickTitle(item, locale) }} <span class="text-slate-500">— {{ item.date || '—' }}</span>
            </li>
          </ul>
          <p v-else class="text-sm text-slate-500">{{ t('secretariatReports.empty') }}</p>
        </div>
      </section>
    </article>
  </div>
</template>

<style>
@media print {
  aside, nav, .no-print {
    display: none !important;
  }
  body {
    background: white !important;
  }
  #report-sheet {
    border: 0 !important;
    box-shadow: none !important;
    padding: 0 !important;
  }
}
</style>
