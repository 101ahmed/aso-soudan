<script setup>
import { reactive, ref } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { resolvePostLoginPath } from '@/utils/roleRedirect'

const { t } = useI18n()
const auth = useAuthStore()
const router = useRouter()
const route = useRoute()
const formError = ref('')

const form = reactive({
  email: localStorage.getItem('rdp_remember_email') || 'admin@acs-rennes.fr',
  password: 'Password123!',
  remember: Boolean(localStorage.getItem('rdp_remember_email')),
})

async function submit() {
  formError.value = ''
  try {
    const user = await auth.login({
      email: form.email,
      password: form.password,
    })

    if (form.remember) {
      localStorage.setItem('rdp_remember_email', form.email)
    } else {
      localStorage.removeItem('rdp_remember_email')
    }

    const redirect =
      typeof route.query.redirect === 'string'
        ? route.query.redirect
        : resolvePostLoginPath(user)

    router.replace(redirect)
  } catch (error) {
    formError.value =
      error.userMessage ||
      error.response?.data?.errors?.email?.[0] ||
      error.response?.data?.message ||
      auth.error ||
      error.message ||
      t('auth.loginFailed')
  }
}
</script>

<template>
  <div class="flex min-h-screen items-center justify-center bg-[var(--rdp-cream)] px-4">
    <form
      class="w-full max-w-md rounded-2xl border border-slate-200 bg-white p-8 shadow-sm"
      @submit.prevent="submit"
    >
      <RouterLink to="/" class="mb-4 inline-flex items-center gap-2 text-sm text-[var(--rdp-forest)] hover:underline">
        ← {{ t('nav.home') }}
      </RouterLink>

      <div class="flex items-center gap-3">
        <img src="/logo.png" :alt="t('app.name')" class="h-16 w-auto rounded-md bg-white object-contain" />
        <div>
          <p class="text-sm font-medium text-teal-800">{{ t('app.name') }}</p>
          <h1 class="text-2xl font-semibold">{{ t('auth.loginTitle') }}</h1>
        </div>
      </div>
      <p class="mt-2 text-sm text-slate-600">{{ t('auth.loginSubtitle') }}</p>

      <div class="mt-6 space-y-4">
        <label class="block text-sm">
          <span class="mb-1 block text-slate-700">{{ t('auth.email') }}</span>
          <input
            v-model="form.email"
            type="text"
            inputmode="email"
            autocomplete="username"
            required
            class="w-full rounded-md border border-slate-300 px-3 py-2 outline-none focus:border-teal-700"
          />
        </label>
        <label class="block text-sm">
          <span class="mb-1 block text-slate-700">{{ t('auth.password') }}</span>
          <input
            v-model="form.password"
            type="password"
            autocomplete="current-password"
            required
            class="w-full rounded-md border border-slate-300 px-3 py-2 outline-none focus:border-teal-700"
          />
        </label>
        <div class="flex items-center justify-between gap-3 text-sm">
          <label class="flex items-center gap-2">
            <input v-model="form.remember" type="checkbox" />
            <span>{{ t('auth.remember') }}</span>
          </label>
          <RouterLink to="/forgot-password" class="text-[var(--rdp-forest)] hover:underline">
            {{ t('auth.forgot') }}
          </RouterLink>
        </div>
      </div>

      <p v-if="formError" class="mt-4 text-sm text-rose-700">{{ formError }}</p>

      <button
        type="submit"
        class="mt-6 w-full rounded-md bg-teal-800 px-4 py-2.5 text-sm font-medium text-white hover:bg-teal-700 disabled:opacity-60"
        :disabled="auth.loading"
      >
        {{ auth.loading ? t('auth.loggingIn') : t('auth.login') }}
      </button>
    </form>
  </div>
</template>
