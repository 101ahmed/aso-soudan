<script setup>
import { computed, ref } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { applyDocumentDirection } from '@/i18n'

const { t, locale } = useI18n()
const route = useRoute()
const menuOpen = ref(false)
const isHome = computed(() => route.name === 'home')

const links = computed(() => [
  { to: '/', label: t('nav.home') },
  { to: '/about', label: t('nav.about') },
  { to: '/secretariats', label: t('nav.secretariats') },
  { to: '/shura-council', label: t('nav.shura') },
  { to: '/parents-council', label: t('nav.parents') },
  { to: '/news', label: t('nav.news') },
  { to: '/events', label: t('nav.events') },
  { to: '/gallery', label: t('nav.gallery') },
  { to: '/contact', label: t('nav.contact') },
])

function setLocale(nextLocale) {
  locale.value = nextLocale
  localStorage.setItem('rdp_locale', nextLocale)
  applyDocumentDirection(nextLocale)
}

function closeMenu() {
  menuOpen.value = false
}
</script>

<template>
  <div class="min-h-screen bg-[var(--rdp-cream)] text-[var(--rdp-ink)]">
    <header
      class="inset-x-0 top-0 z-30"
      :class="isHome ? 'absolute' : 'relative border-b border-black/10 bg-white/95 backdrop-blur'"
    >
      <div class="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-4 md:px-8">
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
          <div
            class="flex overflow-hidden rounded text-xs"
            :class="isHome ? 'border border-white/35' : 'border border-slate-300'"
          >
            <button
              type="button"
              class="px-2 py-1"
              :class="locale === 'fr'
                ? (isHome ? 'bg-white text-[var(--rdp-forest)]' : 'bg-[var(--rdp-forest)] text-white')
                : (isHome ? 'text-white' : '')"
              @click="setLocale('fr')"
            >
              FR
            </button>
            <button
              type="button"
              class="px-2 py-1"
              :class="locale === 'ar'
                ? (isHome ? 'bg-white text-[var(--rdp-forest)]' : 'bg-[var(--rdp-forest)] text-white')
                : (isHome ? 'text-white' : '')"
              @click="setLocale('ar')"
            >
              AR
            </button>
          </div>

          <RouterLink
            to="/login"
            class="hidden rounded px-3 py-2 text-sm font-semibold sm:inline-flex"
            :class="isHome
              ? 'bg-[var(--rdp-gold)] text-[var(--rdp-ink)]'
              : 'bg-[var(--rdp-forest)] text-white'"
          >
            {{ t('nav.login') }}
          </RouterLink>

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
        v-if="menuOpen"
        class="border-t border-black/10 bg-white px-4 py-4 shadow-lg lg:hidden"
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
            to="/login"
            class="mt-2 rounded bg-[var(--rdp-forest)] px-3 py-2 text-center font-semibold text-white"
            @click="closeMenu"
          >
            {{ t('nav.login') }}
          </RouterLink>
        </div>
      </div>
    </header>

    <main>
      <RouterView />
    </main>
  </div>
</template>
