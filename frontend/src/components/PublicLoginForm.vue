<script setup>
import { reactive, ref } from 'vue'
import { RouterLink, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { resolvePostLoginPath } from '@/utils/roleRedirect'

const emit = defineEmits(['success'])

const { t } = useI18n()
const auth = useAuthStore()
const router = useRouter()
const formError = ref('')

const form = reactive({
  email: localStorage.getItem('rdp_remember_email') || '',
  password: '',
  remember: Boolean(localStorage.getItem('rdp_remember_email')),
})

async function submit() {
  formError.value = ''
  try {
    const user = await auth.login({
      email: form.email,
      password: form.password,
      remember: form.remember,
    })

    if (form.remember) {
      localStorage.setItem('rdp_remember_email', form.email)
    } else {
      localStorage.removeItem('rdp_remember_email')
    }

    emit('success')
    router.replace(resolvePostLoginPath(user))
  } catch (error) {
    const network = error.userMessage === 'network' || error.message === 'Network Error' || error.code === 'ERR_NETWORK'
    formError.value = network
      ? t('auth.networkError')
      : error.response?.data?.errors?.email?.[0] ||
        error.response?.data?.message ||
        auth.error ||
        t('auth.loginFailed')
  }
}
</script>

<template>
  <form class="space-y-3" @submit.prevent="submit">
    <label class="block text-sm">
      <span class="mb-1 block text-slate-700">{{ t('auth.email') }}</span>
      <input
        v-model="form.email"
        type="text"
        inputmode="email"
        autocomplete="username"
        required
        class="w-full rounded-md border border-slate-300 px-3 py-2 text-[var(--rdp-ink)] outline-none focus:border-[var(--rdp-forest)]"
      />
    </label>
    <label class="block text-sm">
      <span class="mb-1 block text-slate-700">{{ t('auth.password') }}</span>
      <input
        v-model="form.password"
        type="password"
        autocomplete="current-password"
        required
        class="w-full rounded-md border border-slate-300 px-3 py-2 text-[var(--rdp-ink)] outline-none focus:border-[var(--rdp-forest)]"
      />
    </label>
    <div class="flex items-center justify-between gap-3 text-sm">
      <label class="flex items-center gap-2 text-slate-700">
        <input v-model="form.remember" type="checkbox" />
        <span>{{ t('auth.remember') }}</span>
      </label>
      <RouterLink to="/forgot-password" class="text-[var(--rdp-forest)] hover:underline">
        {{ t('auth.forgot') }}
      </RouterLink>
    </div>
    <p v-if="formError" class="text-sm text-rose-700">{{ formError }}</p>
    <button
      type="submit"
      class="w-full rounded-md bg-[var(--rdp-forest)] px-4 py-2.5 text-sm font-semibold text-white hover:opacity-90 disabled:opacity-60"
      :disabled="auth.loading"
    >
      {{ auth.loading ? t('auth.loggingIn') : t('auth.login') }}
    </button>
  </form>
</template>
