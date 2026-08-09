<script setup>
import { reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import PageHero from '@/components/public/PageHero.vue'

const { t } = useI18n()
const sent = ref(false)
const form = reactive({
  name: '',
  email: '',
  phone: '',
  subject: '',
  message: '',
})

function submit() {
  sent.value = true
}
</script>

<template>
  <div>
    <PageHero :title="t('nav.contact')" :subtitle="t('pages.contact.subtitle')" />
    <section class="mx-auto max-w-2xl px-5 py-12 md:px-8">
      <p v-if="sent" class="rounded-xl bg-white p-6 text-[var(--rdp-forest)]">
        {{ t('pages.contact.success') }}
      </p>
      <form v-else class="space-y-4 rounded-xl bg-white p-6 shadow-sm" @submit.prevent="submit">
        <input v-model="form.name" required :placeholder="t('forms.name')" class="w-full rounded border border-slate-300 px-3 py-2" />
        <input v-model="form.email" required type="email" :placeholder="t('forms.email')" class="w-full rounded border border-slate-300 px-3 py-2" />
        <input v-model="form.phone" :placeholder="t('forms.phoneOptional')" class="w-full rounded border border-slate-300 px-3 py-2" />
        <input v-model="form.subject" required :placeholder="t('forms.subject')" class="w-full rounded border border-slate-300 px-3 py-2" />
        <textarea v-model="form.message" required rows="5" :placeholder="t('forms.message')" class="w-full rounded border border-slate-300 px-3 py-2" />
        <button type="submit" class="rounded bg-[var(--rdp-forest)] px-5 py-2.5 text-sm font-semibold text-white">
          {{ t('forms.send') }}
        </button>
      </form>
    </section>
  </div>
</template>
