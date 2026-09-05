<script setup>
import { computed, provide, ref } from 'vue'
import { RouterLink, RouterView, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import PresidentDirectiveDialog from '../president/PresidentDirectiveDialog.vue'

const { t } = useI18n()
const auth = useAuthStore()
const route = useRoute()

const allowed = computed(() =>
  auth.user?.roles?.some((r) => ['VICE_PRESIDENT', 'PRESIDENT', 'SUPER_ADMIN'].includes(r.code)),
)

const directiveOpen = ref(false)
const directiveTick = ref(0)

const links = computed(() => [
  { to: '/admin/vice-president', label: t('vicePresidentAdmin.home'), exact: true },
  { to: '/admin/vice-president/card', label: t('vicePresidentAdmin.card') },
  { to: '/admin/vice-president/meetings', label: t('presidentAdmin.meetings') },
  { to: '/admin/vice-president/archive', label: t('presidentAdmin.archive') },
])

function isActive(link) {
  if (link.exact) return route.path === link.to
  return route.path.startsWith(link.to)
}

function openDirective() {
  directiveOpen.value = true
}

function onDirectiveSent() {
  directiveTick.value += 1
}

provide('presidentDirectiveTick', directiveTick)
provide('openPresidentDirective', openDirective)
</script>

<template>
  <section v-if="!allowed" class="rounded-xl border border-rose-200 bg-rose-50 p-8 text-center">
    <p class="text-lg font-semibold text-rose-800">403</p>
    <p class="mt-2 text-sm text-rose-700">{{ t('vicePresidentAdmin.forbidden') }}</p>
  </section>

  <section v-else class="space-y-6">
    <div class="flex flex-wrap items-end justify-between gap-3">
      <div>
        <p class="text-xs tracking-wide text-slate-500 uppercase">{{ t('vicePresidentAdmin.badge') }}</p>
        <h1 class="mt-1 text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('vicePresidentAdmin.title') }}</h1>
        <p class="mt-1 text-sm text-slate-600">{{ t('vicePresidentAdmin.note') }}</p>
      </div>
      <button
        type="button"
        class="rounded-lg bg-teal-800 px-4 py-2 text-sm font-semibold text-white hover:bg-teal-900"
        @click="openDirective"
      >
        {{ t('presidentAdmin.sendDirective') }}
      </button>
    </div>

    <nav class="flex flex-wrap gap-2 border-b border-slate-200 pb-3">
      <RouterLink
        v-for="link in links"
        :key="link.to"
        :to="link.to"
        class="rounded-md px-3 py-1.5 text-sm"
        :class="isActive(link) ? 'bg-teal-800 text-white' : 'bg-white text-slate-700 hover:bg-slate-100'"
      >
        {{ link.label }}
      </RouterLink>
    </nav>

    <RouterView />

    <PresidentDirectiveDialog v-model="directiveOpen" @sent="onDirectiveSent" />
  </section>
</template>
