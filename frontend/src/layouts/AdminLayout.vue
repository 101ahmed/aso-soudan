<script setup>
import { computed } from 'vue'
import { RouterLink, RouterView, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import {
  departmentCodesForUser,
  isSecretariatCode,
  SECRETARIAT_CODES,
  SECRETARIAT_NAME_KEYS,
} from '@/utils/departmentAccess'
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'

const { t } = useI18n()
const auth = useAuthStore()
const router = useRouter()
const route = useRoute()

const isTeacher = computed(() => auth.user?.roles?.some((r) => r.code === 'TEACHER'))
const isSuperAdmin = computed(() => auth.user?.roles?.some((r) => r.code === 'SUPER_ADMIN'))
const isPresident = computed(() => auth.user?.roles?.some((r) => r.code === 'PRESIDENT'))
const isVicePresident = computed(() => auth.user?.roles?.some((r) => r.code === 'VICE_PRESIDENT'))
const canBrowseAllSecretariats = computed(() => isSuperAdmin.value || isPresident.value || isVicePresident.value)

const secretariatCodes = computed(() => {
  if (canBrowseAllSecretariats.value) return SECRETARIAT_CODES
  return departmentCodesForUser(auth.user).filter(isSecretariatCode)
})

const teacherOnly = computed(() => isTeacher.value && !isSuperAdmin.value && !secretariatCodes.value.length)

const links = computed(() => [
  { to: '/admin', label: t('admin.nav.dashboard'), show: !teacherOnly.value, exact: true },
  {
    to: '/admin/reports',
    label: t('admin.nav.reports'),
    show: auth.hasPermission('report.view'),
  },
  {
    to: '/admin/teacher',
    label: t('admin.nav.teacher'),
    show: isTeacher.value || isSuperAdmin.value,
  },
  {
    to: '/admin/president',
    label: t('admin.nav.president'),
    show: isPresident.value || isVicePresident.value || isSuperAdmin.value,
  },
  {
    to: '/admin/vice-president',
    label: t('admin.nav.vicePresident'),
    show: isVicePresident.value || isPresident.value || isSuperAdmin.value,
  },
  {
    to: '/admin/content',
    label: t('admin.nav.content'),
    show: auth.hasPermission('news.view') || auth.hasPermission('announcement.view'),
  },
  {
    to: '/admin/shura',
    label: t('admin.nav.shura'),
    show: auth.user?.roles?.some((r) => String(r.code).startsWith('SHURA_'))
      || auth.hasPermission('shura.member.view'),
  },
  {
    to: '/admin/parents',
    label: t('admin.nav.parents'),
    show: auth.user?.roles?.some((r) => String(r.code).startsWith('PARENTS_'))
      || auth.hasPermission('parents.member.view')
      || auth.hasPermission('parents.registration.view')
      || auth.hasPermission('parents.meeting.view')
      || auth.hasPermission('parents.survey.view'),
  },
  { to: '/admin/users', label: t('admin.nav.users'), show: auth.hasPermission('user.view') },
  { to: '/admin/roles', label: t('admin.nav.roles'), show: auth.hasPermission('role.view') },
].filter((link) => link.show))

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}

function isActive(to, exact = false) {
  if (exact) return route.path === to
  return route.path.startsWith(to)
}
</script>

<template>
  <div class="min-h-screen bg-slate-100 text-slate-900">
    <div class="flex min-h-screen">
      <aside class="no-print w-64 shrink-0 border-e border-slate-200 bg-slate-900 text-slate-100">
        <div class="border-b border-slate-700 px-5 py-5">
          <RouterLink to="/" class="block">
            <img src="/logo.png" :alt="t('app.name')" class="mb-3 h-14 w-auto rounded-md bg-white object-contain px-2 py-1" />
            <p class="text-xs tracking-wide text-teal-300">Rennes · RDP</p>
            <h1 class="mt-1 text-base font-semibold leading-snug">{{ t('app.name') }}</h1>
          </RouterLink>
        </div>
        <nav class="flex flex-col gap-1 p-3">
          <RouterLink
            v-for="link in links"
            :key="link.to"
            :to="link.to"
            class="rounded-md px-3 py-2 text-sm text-slate-200 transition hover:bg-slate-800"
            :class="isActive(link.to, link.exact) ? 'bg-teal-800 text-white hover:bg-teal-800' : ''"
          >
            {{ link.label }}
          </RouterLink>

          <div v-if="secretariatCodes.length" class="mt-3 border-t border-slate-700 pt-3">
            <p class="px-3 pb-2 text-[11px] font-semibold tracking-wide text-teal-300 uppercase">
              {{ t('admin.nav.secretariats') }}
            </p>
            <RouterLink
              v-for="code in secretariatCodes"
              :key="code"
              :to="`/admin/secretariats/${code}/inbox`"
              class="rounded-md px-3 py-1.5 text-sm text-slate-200 transition hover:bg-slate-800"
              :class="isActive(`/admin/secretariats/${code}`) ? 'bg-teal-800 text-white hover:bg-teal-800' : ''"
            >
              {{ t(SECRETARIAT_NAME_KEYS[code]) }}
            </RouterLink>
          </div>
        </nav>
      </aside>

      <div class="flex min-w-0 flex-1 flex-col">
        <header class="no-print flex items-center justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
          <div>
            <p class="text-sm text-slate-500">{{ t('admin.header.signedInAs') }}</p>
            <p class="font-medium">{{ auth.fullName || auth.user?.email }}</p>
          </div>
          <div class="flex items-center gap-3">
            <LanguageSwitcher variant="admin" />
            <RouterLink
              to="/"
              class="rounded-md bg-teal-800 px-3 py-1.5 text-sm font-medium text-white hover:bg-teal-700"
            >
              {{ t('admin.header.backToHome') }}
            </RouterLink>
            <button
              type="button"
              class="rounded-md border border-slate-300 px-3 py-1.5 text-sm hover:bg-slate-50"
              @click="logout"
            >
              {{ t('admin.header.logout') }}
            </button>
          </div>
        </header>

        <main class="flex-1 p-6">
          <RouterView />
        </main>
      </div>
    </div>
  </div>
</template>
