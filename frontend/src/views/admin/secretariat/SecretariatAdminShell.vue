<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { fetchMyDepartments } from '@/services/content'
import {
  canAccessDepartment,
  isSecretariatCode,
  SECRETARIAT_NAME_KEYS,
} from '@/utils/departmentAccess'
import { pickName } from '@/utils/localized'

const props = defineProps({
  code: { type: String, required: true },
})

const { t, locale } = useI18n()
const auth = useAuthStore()
const route = useRoute()
const router = useRouter()
const departments = ref([])

const allowed = computed(() => canAccessDepartment(auth.user, props.code, { write: false }))
const base = computed(() => `/admin/secretariats/${props.code}`)
const switchable = computed(() =>
  departments.value.filter((dept) => isSecretariatCode(dept.code) && canAccessDepartment(auth.user, dept.code)),
)

const links = computed(() => {
  const items = [
    { to: base.value, label: t('secretariatAdmin.home'), exact: true },
  ]
  if (auth.hasPermission('inbox.view')) {
    items.push({ to: `${base.value}/messages`, label: t('secretariatAdmin.messages') })
  }
  if (
    auth.hasPermission('president.directive.inbox')
    || auth.hasPermission('inbox.view')
    || auth.user?.roles?.some((r) => ['SUPER_ADMIN', 'PRESIDENT'].includes(r.code))
  ) {
    items.push({ to: `${base.value}/presidential-directives`, label: t('secretariatAdmin.presidentialDirectives') })
  }
  items.push(
    { to: `${base.value}/officer`, label: t('secretariatAdmin.officer') },
    { to: `${base.value}/deputy`, label: t('secretariatAdmin.deputy') },
    { to: `${base.value}/news`, label: t('secretariatAdmin.news') },
    { to: `${base.value}/events`, label: t('secretariatAdmin.events') },
    { to: `${base.value}/announcements`, label: t('secretariatAdmin.announcements') },
    { to: `${base.value}/albums`, label: t('secretariatAdmin.albums') },
  )
  if (props.code === 'academic' && (auth.hasPermission('attendance.view') || auth.hasPermission('student.view'))) {
    items.push({ to: `${base.value}/attendance`, label: t('secretariatAdmin.attendance') })
    items.push({ to: `${base.value}/timetable`, label: t('secretariatAdmin.timetable') })
  }
  if (props.code === 'academic' && auth.hasPermission('teacher.view')) {
    items.push({ to: `${base.value}/teachers`, label: t('secretariatAdmin.teachers') })
  }
  if (props.code === 'academic' && auth.hasPermission('student.view')) {
    items.push({ to: `${base.value}/students`, label: t('secretariatAdmin.students') })
  }
  if (props.code === 'statistics' && auth.hasPermission('member.view')) {
    items.push({ to: `${base.value}/members`, label: t('secretariatAdmin.members') })
  }
  if (props.code === 'social' && auth.hasPermission('help.view')) {
    items.push({ to: `${base.value}/help-requests`, label: t('secretariatAdmin.helpRequests') })
  }
  if (props.code === 'finance' && auth.hasPermission('finance.view')) {
    items.push({ to: `${base.value}/accounts`, label: t('secretariatAdmin.financeOverview') })
    items.push({ to: `${base.value}/revenues`, label: t('secretariatAdmin.financeRevenues') })
    items.push({ to: `${base.value}/expenses`, label: t('secretariatAdmin.financeExpenses') })
  }
  if ((props.code === 'media' || props.code === 'general') && auth.hasPermission('decision.view')) {
    items.push({ to: `${base.value}/decisions`, label: props.code === 'general' ? t('secretariatAdmin.executiveDecisions') : t('secretariatAdmin.decisions') })
  }
  if (props.code === 'media' && auth.hasPermission('press.view')) {
    items.push({ to: `${base.value}/media-center`, label: t('secretariatAdmin.mediaCenter') })
  }
  if (props.code === 'external-relations' && auth.hasPermission('partner.view')) {
    items.push({ to: `${base.value}/partners`, label: t('secretariatAdmin.partners') })
    items.push({ to: `${base.value}/files`, label: t('secretariatAdmin.externalFiles') })
  }
  if (props.code === 'external-relations' && auth.hasPermission('extcontact.view')) {
    items.push({ to: `${base.value}/contact-requests`, label: t('secretariatAdmin.contactRequests') })
  }
  if (auth.hasPermission('report.view')) {
    items.push({ to: `${base.value}/reports`, label: t('secretariatAdmin.reports') })
  }
  return items
})

function isActive(link) {
  if (link.exact) return route.path === link.to
  return route.path.startsWith(link.to)
}

function switchSecretariat(nextCode) {
  const suffix = route.path.slice(base.value.length) || ''
  router.push(`/admin/secretariats/${nextCode}${suffix}`)
}

const title = computed(() => {
  const fromApi = departments.value.find((d) => d.code === props.code)
  if (fromApi) return pickName(fromApi, locale.value)
  const dept = (auth.user?.departments || []).find((d) => d.code === props.code)
  if (dept) return pickName(dept, locale.value)
  return SECRETARIAT_NAME_KEYS[props.code] ? t(SECRETARIAT_NAME_KEYS[props.code]) : props.code
})

const unreadDirectives = computed(() => {
  const dept = departments.value.find((d) => d.code === props.code)
  return Number(dept?.unread_directives_count || 0)
})

onMounted(async () => {
  try {
    departments.value = await fetchMyDepartments()
  } catch {
    departments.value = []
  }
})
</script>

<template>
  <section v-if="!allowed" class="rounded-xl border border-rose-200 bg-rose-50 p-8 text-center">
    <p class="text-lg font-semibold text-rose-800">403</p>
    <p class="mt-2 text-sm text-rose-700">{{ t('secretariatAdmin.forbidden') }}</p>
    <RouterLink to="/admin" class="mt-4 inline-flex text-sm text-[var(--rdp-forest)] hover:underline">
      {{ t('admin.nav.dashboard') }}
    </RouterLink>
  </section>

  <section v-else class="space-y-6">
    <div class="no-print flex flex-wrap items-end justify-between gap-3">
      <div>
        <p class="text-xs tracking-wide text-slate-500 uppercase">{{ t('secretariatAdmin.badge') }}</p>
        <h1 class="mt-1 text-2xl font-semibold text-[var(--rdp-forest)]">{{ title }}</h1>
      </div>
      <label v-if="switchable.length > 1" class="text-sm text-slate-600">
        {{ t('secretariatAdmin.switch') }}
        <select
          class="ms-2 rounded border border-slate-300 bg-white px-3 py-1.5 text-sm"
          :value="code"
          @change="switchSecretariat($event.target.value)"
        >
          <option v-for="dept in switchable" :key="dept.code" :value="dept.code">
            {{ pickName(dept, locale) || t(SECRETARIAT_NAME_KEYS[dept.code] || dept.code) }}
          </option>
        </select>
      </label>
    </div>

    <nav class="no-print flex flex-wrap gap-2 border-b border-slate-200 pb-3">
      <RouterLink
        v-for="link in links"
        :key="link.to"
        :to="link.to"
        class="rounded-md px-3 py-1.5 text-sm"
        :class="isActive(link) ? 'bg-teal-800 text-white' : 'bg-white text-slate-700 hover:bg-slate-100'"
      >
        {{ link.label }}
        <span
          v-if="link.to.endsWith('/presidential-directives') && unreadDirectives > 0"
          class="ms-1 rounded-full bg-amber-200 px-1.5 text-[10px] font-semibold text-amber-900"
        >
          {{ unreadDirectives }}
        </span>
      </RouterLink>
    </nav>

    <RouterView :key="route.fullPath" />
  </section>
</template>
