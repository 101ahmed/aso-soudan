<script setup>
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'

const { t } = useI18n()
const auth = useAuthStore()
</script>

<template>
  <section class="space-y-4">
    <h1 class="text-2xl font-semibold">{{ t('admin.dashboard.title') }}</h1>
    <p class="text-slate-600">{{ t('admin.dashboard.welcome', { name: auth.fullName }) }}</p>

    <div class="grid gap-4 md:grid-cols-3">
      <div class="rounded-xl border border-slate-200 bg-white p-5">
        <p class="text-sm text-slate-500">{{ t('admin.dashboard.roles') }}</p>
        <p class="mt-2 text-lg font-medium">
          {{ auth.user?.roles?.map((r) => r.code).join(', ') || '—' }}
        </p>
      </div>
      <div class="rounded-xl border border-slate-200 bg-white p-5 md:col-span-2">
        <p class="text-sm text-slate-500">{{ t('admin.dashboard.permissions') }}</p>
        <p class="mt-2 text-sm text-slate-700">
          {{ (auth.user?.permissions || []).slice(0, 8).join(' · ') }}
          <span v-if="(auth.user?.permissions || []).length > 8">…</span>
        </p>
      </div>
    </div>
  </section>
</template>
