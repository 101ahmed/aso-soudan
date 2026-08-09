<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'

const { t } = useI18n()
const email = ref('')
const sent = ref(false)

function submit() {
  sent.value = true
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-[var(--rdp-cream)] px-4">
    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
      <RouterLink to="/login" class="text-sm text-[var(--rdp-forest)] hover:underline">
        ← {{ t('auth.login') }}
      </RouterLink>
      <h1 class="mt-4 text-2xl font-semibold">{{ t('auth.forgotTitle') }}</h1>
      <p class="mt-2 text-sm text-slate-600">{{ t('auth.forgotSubtitle') }}</p>

      <p v-if="sent" class="mt-6 text-sm text-teal-800">{{ t('auth.forgotSent') }}</p>
      <form v-else class="mt-6 space-y-4" @submit.prevent="submit">
        <input
          v-model="email"
          type="email"
          required
          :placeholder="t('auth.email')"
          class="w-full rounded-md border border-slate-300 px-3 py-2"
        />
        <button type="submit" class="w-full rounded-md bg-teal-800 px-4 py-2.5 text-sm font-medium text-white">
          {{ t('forms.send') }}
        </button>
      </form>
    </div>
  </div>
</template>
