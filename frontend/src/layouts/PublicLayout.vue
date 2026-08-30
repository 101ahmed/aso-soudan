<script setup>
import { computed, ref } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { resolveAdminEntryPath } from '@/utils/roleRedirect'
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'
import PublicLoginForm from '@/components/PublicLoginForm.vue'

const { t } = useI18n()
const route = useRoute()
const auth = useAuthStore()
const menuOpen = ref(false)
const loginOpen = ref(false)
const isHome = computed(() => route.name === 'home')
const adminPath = computed(() => resolveAdminEntryPath(auth.user))

const links = computed(() => [
  { to: '/', label: t('nav.home') },
  { to: '/about', label: t('nav.about') },
  { to: '/president', label: t('nav.president') },
  { to: '/secretariats', label: t('nav.secretariats') },
  { to: '/shura-council', label: t('nav.shura') },
  { to: '/parents-council', label: t('nav.parents') },
  { to: '/news', label: t('nav.news') },
  { to: '/events', label: t('nav.events') },
  { to: '/gallery', label: t('nav.gallery') },
  { to: '/contact', label: t('nav.contact') },
])

function closeMenu() {
  menuOpen.value = false
  loginOpen.value = false
}

function toggleLogin() {
  if (auth.isAuthenticated) return
  loginOpen.value = !loginOpen.value
}
</script>

<template>
  <div class="min-h-screen bg-[var(--rdp-cream)] text-[var(--rdp-ink)]">
    <header
      class="inset-x-0 top-0 z-30"
      :class="isHome ? 'absolute' : 'relative border-b border-black/10 bg-white/95 backdrop-blur'"
    >
      <div class="relative z-50 mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-4 md:px-8">
        <RouterLink to="/" class="flex min-w-0 items-center gap-3" @click="closeMenu">
          <img
            src="/logo.png"
            :alt="t('app.name')"
            class="h-11 w-auto shrink-0 rounded-md bg-white object-contain px-1 py-0.5 shadow-sm md:h-12"
          />
          <span
            class="hidden max-w-[12rem] truncate text-sm font-semibold leading-tight sm:inline md:max-w-xs md:text-base"
            :class="isHome ? 'text-white' : 'text-[var(--rdp-forest)]'"
          >
            {{ t('app.name') }}
          </span>
        </RouterLink>

        <nav
          class="hidden items-center gap-3 text-sm lg:flex xl:gap-4"
          :class="isHome ? 'text-white/90' : 'text-slate-700'"
        >
          <RouterLink
            v-for="link in links"
            :key="link.to"
            :to="link.to"
            class="whitespace-nowrap transition hover:opacity-100"
            :class="isHome ? 'hover:text-white' : 'hover:text-[var(--rdp-forest)]'"
          >
            {{ link.label }}
          </RouterLink>
        </nav>

        <div class="flex items-center gap-2">
          <LanguageSwitcher :is-home="isHome" />

          <div class="relative hidden sm:block">
            <RouterLink
              v-if="auth.isAuthenticated"
              :to="adminPath"
              class="rounded px-3 py-2 text-sm font-semibold"
              :class="isHome
                ? 'bg-[var(--rdp-gold)] text-[var(--rdp-ink)]'
                : 'bg-[var(--rdp-forest)] text-white'"
            >
              {{ t('nav.admin') }}
            </RouterLink>
            <template v-else>
              <button
                type="button"
                class="rounded px-3 py-2 text-sm font-semibold"
                :class="isHome
                  ? 'bg-[var(--rdp-gold)] text-[var(--rdp-ink)]'
                  : 'bg-[var(--rdp-forest)] text-white'"
                :aria-expanded="loginOpen"
                @click.stop="toggleLogin"
              >
                {{ t('nav.login') }}
              </button>
              <div
                v-if="loginOpen"
                class="absolute top-full z-50 mt-2 w-80 rounded-xl border border-slate-200 bg-white p-4 shadow-xl end-0"
                @click.stop
              >
                <p class="mb-3 text-sm font-semibold text-[var(--rdp-forest)]">{{ t('auth.loginTitle') }}</p>
                <PublicLoginForm @success="closeMenu" />
              </div>
            </template>
          </div>

          <button
            type="button"
            class="rounded border px-2.5 py-1.5 text-sm lg:hidden"
            :class="isHome ? 'border-white/40 text-white' : 'border-slate-300 text-slate-800'"
            @click="menuOpen = !menuOpen"
          >
            {{ menuOpen ? t('nav.close') : t('nav.menu') }}
          </button>
        </div>
      </div>

      <div
        v-if="loginOpen"
        class="fixed inset-0 z-40"
        @click="loginOpen = false"
      />

      <div
        v-if="menuOpen"
        class="relative z-50 border-t border-black/10 bg-white px-4 py-4 shadow-lg lg:hidden"
      >
        <div class="mx-auto flex max-w-6xl flex-col gap-2 text-sm">
          <RouterLink
            v-for="link in links"
            :key="link.to"
            :to="link.to"
            class="rounded px-3 py-2 text-slate-800 hover:bg-slate-100"
            @click="closeMenu"
          >
            {{ link.label }}
          </RouterLink>
          <RouterLink
            v-if="auth.isAuthenticated"
            :to="adminPath"
            class="mt-2 rounded bg-[var(--rdp-forest)] px-3 py-2 text-center font-semibold text-white"
            @click="closeMenu"
          >
            {{ t('nav.admin') }}
          </RouterLink>
          <div v-else class="mt-2 rounded-xl border border-slate-200 bg-[var(--rdp-cream)] p-4">
            <p class="mb-3 text-center font-semibold text-[var(--rdp-forest)]">{{ t('auth.loginTitle') }}</p>
            <PublicLoginForm @success="closeMenu" />
          </div>
        </div>
      </div>
    </header>

    <main>
      <RouterView />
    </main>
  </div>
</template>
