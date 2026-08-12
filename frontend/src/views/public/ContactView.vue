<script setup>
import { onMounted, reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import PageHero from '@/components/public/PageHero.vue'
import api from '@/services/api'

const { t } = useI18n()
const sent = ref(false)
const sending = ref(false)
const error = ref('')
const contactEmail = ref('hima171221@gmail.com')

const form = reactive({
  name: '',
  email: '',
  phone: '',
  subject: '',
  message: '',
})

onMounted(async () => {
  try {
    const { data } = await api.get('/public/contact')
    if (data?.email) contactEmail.value = data.email
  } catch {
    // keep default
  }
})

async function submit() {
  sending.value = true
  error.value = ''
  try {
    await api.post('/public/contact', { ...form })
    sent.value = true
  } catch (e) {
    error.value = e.response?.data?.message || e.userMessage || t('pages.contact.error')
  } finally {
    sending.value = false
  }
}
</script>

<template>
  <div>
    <PageHero :title="t('nav.contact')" :subtitle="t('pages.contact.subtitle')" />
    <section class="mx-auto max-w-2xl space-y-6 px-5 py-12 md:px-8">
      <div class="rounded-xl border border-[var(--rdp-forest)]/15 bg-white p-5 text-sm text-slate-700 shadow-sm">
        <p class="font-medium text-[var(--rdp-forest)]">{{ t('pages.contact.emailLabel') }}</p>
        <a :href="`mailto:${contactEmail}`" class="mt-1 inline-flex text-lg font-semibold text-[var(--rdp-ink)] hover:underline" dir="ltr">
          {{ contactEmail }}
        </a>
      </div>

      <p v-if="sent" class="rounded-xl bg-white p-6 text-[var(--rdp-forest)] shadow-sm">
        {{ t('pages.contact.success') }}
      </p>

      <form v-else class="space-y-4 rounded-xl bg-white p-6 shadow-sm" @submit.prevent="submit">
        <p v-if="error" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">{{ error }}</p>
        <input v-model="form.name" required :placeholder="t('forms.name')" class="w-full rounded border border-slate-300 px-3 py-2" />
        <input v-model="form.email" required type="email" :placeholder="t('forms.email')" class="w-full rounded border border-slate-300 px-3 py-2" />
        <input v-model="form.phone" :placeholder="t('forms.phoneOptional')" class="w-full rounded border border-slate-300 px-3 py-2" />
        <input v-model="form.subject" required :placeholder="t('forms.subject')" class="w-full rounded border border-slate-300 px-3 py-2" />
        <textarea v-model="form.message" required rows="5" :placeholder="t('forms.message')" class="w-full rounded border border-slate-300 px-3 py-2" />
        <button
          type="submit"
          class="rounded bg-[var(--rdp-forest)] px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-60"
          :disabled="sending"
        >
          {{ sending ? t('pages.contact.sending') : t('forms.send') }}
        </button>
      </form>
    </section>
  </div>
</template>
