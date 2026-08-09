<script setup>
import { computed, reactive, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PageHero from '@/components/public/PageHero.vue'
import { upcomingEvents } from '@/data/publicContent'

const route = useRoute()
const { t, locale } = useI18n()
const sent = ref(false)
const form = reactive({ full_name: '', email: '', phone: '' })
const event = computed(() => upcomingEvents.find((item) => item.slug === route.params.slug))
const localized = (value) => value?.[locale.value] || value?.fr || ''

function submit() {
  sent.value = true
}
</script>

<template>
  <div v-if="event">
    <PageHero :title="localized(event.title)" :subtitle="`${event.date} · ${event.time}`" />
    <section class="mx-auto max-w-3xl space-y-4 px-5 py-12 md:px-8">
      <img :src="event.image" alt="" class="h-72 w-full rounded-xl object-cover" />
      <p class="text-slate-600">{{ localized(event.place) }} — {{ localized(event.organizer) }}</p>
      <p class="leading-relaxed text-slate-700">{{ localized(event.summary) }}</p>

      <div v-if="event.registrationOpen" id="register" class="mt-8 rounded-xl border border-[var(--rdp-forest)]/15 bg-white p-6">
        <h2 class="text-lg font-semibold text-[var(--rdp-forest)]">{{ t('home.eventRegister') }}</h2>
        <p v-if="sent" class="mt-3 text-sm text-teal-800">{{ t('pages.events.registered') }}</p>
        <form v-else class="mt-4 grid gap-3" @submit.prevent="submit">
          <input v-model="form.full_name" required :placeholder="t('forms.name')" class="rounded border border-slate-300 px-3 py-2" />
          <input v-model="form.email" required type="email" :placeholder="t('forms.email')" class="rounded border border-slate-300 px-3 py-2" />
          <input v-model="form.phone" :placeholder="t('forms.phoneOptional')" class="rounded border border-slate-300 px-3 py-2" />
          <button type="submit" class="rounded bg-[var(--rdp-forest)] px-4 py-2 text-sm font-semibold text-white">
            {{ t('forms.send') }}
          </button>
        </form>
      </div>
    </section>
  </div>
  <div v-else class="mx-auto max-w-3xl px-5 py-20">{{ t('pages.notFound') }}</div>
</template>
