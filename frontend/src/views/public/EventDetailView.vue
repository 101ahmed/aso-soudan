<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PageHero from '@/components/public/PageHero.vue'
import { upcomingEvents } from '@/data/publicContent'
import { fetchPublicEvent } from '@/services/content'

const route = useRoute()
const { t, locale } = useI18n()
const sent = ref(false)
const loading = ref(true)
const apiEvent = ref(null)
const form = reactive({ full_name: '', email: '', phone: '' })

const staticEvent = computed(() => upcomingEvents.find((item) => item.slug === route.params.slug))

const event = computed(() => {
  if (apiEvent.value) {
    const item = apiEvent.value
    return {
      title: { ar: item.title_ar, fr: item.title_fr },
      summary: { ar: item.description_ar, fr: item.description_fr },
      place: { ar: item.location_ar || item.location, fr: item.location_fr || item.location },
      organizer: {
        ar: item.department?.name_ar,
        fr: item.department?.name_fr,
      },
      image: item.image_url || '/logo.png',
      date: (item.starts_at || item.published_at || '').slice(0, 10),
      time: item.starts_at ? item.starts_at.slice(11, 16) : '',
      type: item.type,
      registrationOpen: false,
    }
  }
  return staticEvent.value
})

const localized = (value) => value?.[locale.value] || value?.en || value?.fr || value?.ar || ''

async function load() {
  loading.value = true
  apiEvent.value = null
  try {
    apiEvent.value = await fetchPublicEvent(route.params.slug)
  } catch {
    apiEvent.value = null
  } finally {
    loading.value = false
  }
}

function submit() {
  sent.value = true
}

onMounted(load)
watch(() => route.params.slug, load)
</script>

<template>
  <div v-if="loading" class="mx-auto max-w-3xl px-5 py-20 text-sm text-slate-500">{{ t('admin.loading') }}</div>
  <div v-else-if="event">
    <PageHero
      :title="localized(event.title)"
      :subtitle="[event.type ? t(`secretariat.eventTypes.${event.type}`) : '', event.date, event.time].filter(Boolean).join(' · ')"
    />
    <section class="mx-auto max-w-3xl space-y-4 px-5 py-12 md:px-8">
      <img :src="event.image" alt="" class="h-72 w-full rounded-xl object-cover" />
      <p class="text-slate-600">
        {{ localized(event.place) }}
        <span v-if="localized(event.organizer)"> — {{ localized(event.organizer) }}</span>
      </p>
      <p class="leading-relaxed whitespace-pre-line text-slate-700">{{ localized(event.summary) }}</p>

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
