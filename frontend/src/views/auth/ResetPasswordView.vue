<script setup>
import { reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import api from '@/services/api'
import LanguageSwitcher from '@/components/LanguageSwitcher.vue'

const { t } = useI18n()
const route = useRoute()
const router = useRouter()

const form = reactive({
  email: typeof route.query.email === 'string' ? route.query.email : '',
  token: typeof route.query.token === 'string' ? route.query.token : '',
  password: '',
  password_confirmation: '',
})

const loading = ref(false)
const error = ref('')
const success = ref(false)

async function submit() {
  error.value = ''
  loading.value = true
  try {
    await api.post('/auth/reset-password', {
      email: form.email,
      token: form.token,
      password: form.password,
      password_confirmation: form.password_confirmation,
    })
    success.value = true
    setTimeout(() => router.replace('/login'), 2000)
  } catch (e) {
    error.value =
      e.userMessage ||
      e.response?.data?.errors?.email?.[0] ||
      e.response?.data?.errors?.password?.[0] ||
      e.response?.data?.message ||
      e.message ||
      t('auth.resetFailed')
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-[var(--rdp-cream)] px-4">
    <div class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm">
      <div class="flex items-center justify-between">
        <RouterLink to="/login" class="text-sm text-[var(--rdp-forest)] hover:underline">
          ← {{ t('auth.login') }}
        </RouterLink>
        <LanguageSwitcher />
      </div>
      <h1 class="mt-4 text-2xl font-semibold">{{ t('auth.resetTitle') }}</h1>
      <p class="mt-2 text-sm text-slate-600">{{ t('auth.resetSubtitle') }}</p>

      <p v-if="success" class="mt-6 text-sm text-teal-800">{{ t('auth.resetSuccess') }}</p>

      <form v-else class="mt-6 space-y-4" @submit.prevent="submit">
        <label class="block text-sm">
          <span class="mb-1 block text-slate-700">{{ t('auth.email') }}</span>
          <input
            v-model="form.email"
            type="email"
            required
            autocomplete="email"
            class="w-full rounded-md border border-slate-300 px-3 py-2"
          />
        </label>
        <label class="block text-sm">
          <span class="mb-1 block text-slate-700">{{ t('auth.newPassword') }}</span>
          <input
            v-model="form.password"
            type="password"
            required
            autocomplete="new-password"
            class="w-full rounded-md border border-slate-300 px-3 py-2"
          />
        </label>
        <label class="block text-sm">
          <span class="mb-1 block text-slate-700">{{ t('auth.confirmPassword') }}</span>
          <input
            v-model="form.password_confirmation"
            type="password"
            required
            autocomplete="new-password"
            class="w-full rounded-md border border-slate-300 px-3 py-2"
          />
        </label>
        <p v-if="!form.token" class="text-sm text-amber-700">{{ t('auth.resetMissingToken') }}</p>
        <p v-if="error" class="text-sm text-rose-700">{{ error }}</p>
        <button
          type="submit"
          class="w-full rounded-md bg-teal-800 px-4 py-2.5 text-sm font-medium text-white disabled:opacity-60"
          :disabled="loading || !form.token"
        >
          {{ loading ? t('auth.resetting') : t('auth.resetSubmit') }}
        </button>
      </form>
    </div>
  </div>
</template>
