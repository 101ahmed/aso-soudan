<script setup>
import { computed } from 'vue'
import { RouterLink, RouterView, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { applyDocumentDirection } from '@/i18n'

const { t, locale } = useI18n()
const auth = useAuthStore()
const router = useRouter()

const links = computed(() => [
  { to: '/admin', label: t('admin.nav.dashboard'), show: true },
  {
    to: '/admin/president',
    label: t('admin.nav.president'),
    show: auth.user?.roles?.some((r) => ['PRESIDENT', 'SUPER_ADMIN'].includes(r.code)),
  },
  { to: '/admin/users', label: t('admin.nav.users'), show: auth.hasPermission('user.view') },
  { to: '/admin/roles', label: t('admin.nav.roles'), show: auth.hasPermission('role.view') },
].filter((link) => link.show))

function setLocale(next) {
  locale.value = next
  localStorage.setItem('rdp_locale', next)
  applyDocumentDirection(next)
}

async function logout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>

<template>
  <div class="min-h-screen bg-slate-100 text-slate-900">
    <div class="flex min-h-screen">
      <aside class="w-64 shrink-0 border-e border-slate-200 bg-slate-900 text-slate-100">
        <div class="border-b border-slate-700 px-5 py-5">
          <img src="/logo.png" :alt="t('app.name')" class="mb-3 h-14 w-auto rounded-md bg-white object-contain px-2 py-1" />
          <p class="text-xs tracking-wide text-teal-300">Rennes · RDP</p>
          <h1 class="mt-1 text-base font-semibold leading-snug">{{ t('app.name') }}</h1>
        </div>
        <nav class="flex flex-col gap-1 p-3">
          <RouterLink
            v-for="link in links"
            :key="link.to"
            :to="link.to"
            class="rounded-md px-3 py-2 text-sm text-slate-200 transition hover:bg-slate-800"
            active-class="bg-teal-800 text-white hover:bg-teal-800"
          >
            {{ link.label }}
          </RouterLink>
        </nav>
      </aside>

      <div class="flex min-w-0 flex-1 flex-col">
        <header class="flex items-center justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
          <div>
            <p class="text-sm text-slate-500">{{ t('admin.header.signedInAs') }}</p>
            <p class="font-medium">{{ auth.fullName || auth.user?.email }}</p>
          </div>
          <div class="flex items-center gap-3">
            <div class="flex overflow-hidden rounded-md border border-slate-300 text-sm">
              <button
                type="button"
                class="px-2.5 py-1"
                :class="locale === 'fr' ? 'bg-teal-800 text-white' : 'bg-white'"
                @click="setLocale('fr')"
              >
                FR
              </button>
              <button
                type="button"
                class="px-2.5 py-1"
                :class="locale === 'ar' ? 'bg-teal-800 text-white' : 'bg-white'"
                @click="setLocale('ar')"
              >
                AR
              </button>
            </div>
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
