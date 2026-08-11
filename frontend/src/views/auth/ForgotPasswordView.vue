<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import api from '@/services/api'

const { t } = useI18n()
const email = ref('')
const loading = ref(false)
const sent = ref(false)
const error = ref('')

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await api.post('/auth/forgot-password', { email: email.value })
    sent.value = true
  } catch (e) {
    error.value =
      e.userMessage ||
      e.response?.data?.message ||
      e.message ||
      t('auth.forgotFailed')
  } finally {
    loading.value = false
  }
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
          autocomplete="email"
          :placeholder="t('auth.email')"
          class="w-full rounded-md border border-slate-300 px-3 py-2"
        />
        <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>
        <button
          type="submit"
          class="w-full rounded-md bg-teal-800 px-4 py-2.5 text-sm font-medium text-white disabled:opacity-60"
          :disabled="loading"
        >
          {{ loading ? t('auth.sending') : t('forms.send') }}
        </button>
      </form>
    </div>
  </div>
</template>
