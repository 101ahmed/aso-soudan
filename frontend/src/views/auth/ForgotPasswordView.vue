<script setup>
import { ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import api from '@/services/api'

const { t, locale } = useI18n()
const email = ref('')
const loading = ref(false)
const sent = ref(false)
const error = ref('')

async function submit() {
  error.value = ''
  loading.value = true
  try {
    // Réveille le free tier Render avant l'envoi mail
    try {
      await api.get('/health', { timeout: 90000 })
    } catch {
      // continuer : le POST peut encore réussir
    }

    await api.post(
      '/auth/forgot-password',
      { email: email.value },
      { timeout: 90000 },
    )
    sent.value = true
  } catch (e) {
    const serverError = e.response?.data?.error || ''
    const serverMsg = e.response?.data?.message || ''

    if (/smtp\.mailersend|Connection timed out|stream_socket_client/i.test(serverError)) {
      error.value =
        locale.value === 'ar'
          ? 'إرسال البريد عبر SMTP محظور على الخادم. استخدم MailerSend API على Render.'
          : 'SMTP bloqué sur Render. Configurez MAIL_MAILER=mailersend + MAILERSEND_API_KEY.'
    } else {
      error.value =
        e.userMessage ||
        serverMsg ||
        e.message ||
        t('auth.forgotFailed')
    }
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
