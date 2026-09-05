<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { getSecretariat } from '@/data/secretariats'
import {
  albumsBySecretariat,
  newsBySecretariat,
} from '@/data/publicContent'
import { fetchSecretariatFeed } from '@/services/content'
import { fetchPublicDocuments, fetchPublicPartners } from '@/services/external'
import { fetchPublicFinanceDocuments } from '@/services/finance'
import { fetchPublicMeetingOutputs } from '@/services/meetingOutputs'
import { submitSecretariatMessage } from '@/services/secretariatMessages'
import EventStarRating from '@/components/public/EventStarRating.vue'
import PhotoGallerySection from '@/components/public/PhotoGallerySection.vue'

const route = useRoute()
const { t, locale } = useI18n()
const sent = ref(false)
const sending = ref(false)
const contactError = ref('')
const feed = ref({ news: [], announcements: [], albums: [], events: [], media_center: [], department: null })
const publicPartners = ref([])
const publicDocuments = ref([])
const financeDocuments = ref([])
const meetingOutputs = ref([])

const form = reactive({
  name: '',
  email: '',
  phone: '',
  subject: '',
  message: '',
})

const baseSecretariat = computed(() => getSecretariat(route.params.slug))

function mergePerson(apiPerson, fallback) {
  if (!apiPerson) return fallback || null
  const hasApiName = apiPerson.name_ar || apiPerson.name_fr
  const hasApiPhoto = apiPerson.photo_url
  if (!hasApiName && !hasApiPhoto) return fallback || null
  return {
    name: {
      ar: apiPerson.name_ar || fallback?.name?.ar,
      fr: apiPerson.name_fr || fallback?.name?.fr,
    },
    title: {
      ar: apiPerson.title_ar || fallback?.title?.ar,
      fr: apiPerson.title_fr || fallback?.title?.fr,
    },
    bio: {
      ar: apiPerson.bio_ar || fallback?.bio?.ar,
      fr: apiPerson.bio_fr || fallback?.bio?.fr,
    },
    email: apiPerson.email || fallback?.email,
    photo: apiPerson.photo_url || null,
  }
}

const secretariat = computed(() => {
  const base = baseSecretariat.value
  if (!base) return null
  return {
    ...base,
    officer: mergePerson(feed.value.department?.officer, base.officer) || base.officer,
    deputy: mergePerson(feed.value.department?.deputy, base.deputy),
  }
})

const localized = (value) => value?.[locale.value] || value?.en || value?.fr || value?.ar || ''

const people = computed(() => {
  const s = secretariat.value
  if (!s) return []
  return [
    { role: 'officer', person: s.officer },
    { role: 'deputy', person: s.deputy },
  ].filter((item) => item.person && (localized(item.person.name) || item.person.photo))
})

const news = computed(() => {
  if (feed.value.news?.length) {
    return feed.value.news.map((item) => ({
      id: item.id,
      slug: item.slug,
      image: item.image_url || '/logo.png',
      date: (item.published_at || '').slice(0, 10),
      title: { ar: item.title_ar, fr: item.title_fr },
    }))
  }
  return newsBySecretariat(route.params.slug)
})

const mediaCenter = computed(() => feed.value.media_center || [])
const announcements = computed(() => feed.value.announcements || [])

const events = computed(() =>
  (feed.value.events || []).map((item) => ({
    id: item.id,
    slug: item.slug,
    type: item.type,
    image: item.image_url || '/logo.png',
    date: (item.starts_at || item.published_at || '').slice(0, 10),
    time: item.starts_at ? item.starts_at.slice(11, 16) : '',
    title: { ar: item.title_ar, fr: item.title_fr },
    summary: { ar: item.description_ar, fr: item.description_fr },
    place: { ar: item.location_ar || item.location, fr: item.location_fr || item.location },
    rating_avg: Number(item.rating_avg) || 0,
    rating_count: Number(item.rating_count) || 0,
    registrationOpen: false,
  })),
)

const albums = computed(() => {
  if (feed.value.albums?.length) {
    return feed.value.albums.map((item) => ({
      id: item.id,
      slug: item.slug || String(item.id),
      cover: item.cover_url || item.media?.[0]?.url || '/logo.png',
      cover_url: item.cover_url,
      title: { ar: item.title_ar, fr: item.title_fr },
      title_ar: item.title_ar,
      title_fr: item.title_fr,
      media: item.media || [],
    }))
  }
  return albumsBySecretariat(route.params.slug)
})

const list = (value) => {
  const items = value?.[locale.value] || value?.en || value?.fr || value?.ar || []
  return Array.isArray(items) ? items : []
}

const canRateEvents = computed(() => route.params.slug === 'women-children')
const isExternal = computed(() => route.params.slug === 'external-relations')
const isFinance = computed(() => route.params.slug === 'finance')
const isGeneral = computed(() => route.params.slug === 'general')

const financeGeneralReport = computed(() =>
  financeDocuments.value.find((item) => item.kind === 'general_report') || null,
)
const financeSubscriptions = computed(() =>
  financeDocuments.value.find((item) => item.kind === 'subscriptions_announcement') || null,
)

const displayedPartners = computed(() => {
  if (isExternal.value && publicPartners.value.length) {
    return publicPartners.value.map((item) => ({
      name: locale.value === 'fr' ? (item.name_fr || item.name_ar) : (item.name_ar || item.name_fr),
      desc: locale.value === 'fr' ? (item.description_fr || item.description_ar || '') : (item.description_ar || item.description_fr || ''),
      website: item.website,
      type: item.type,
    }))
  }
  return list(secretariat.value?.partners)
})

const displayedDocuments = computed(() => {
  if (isFinance.value) {
    return financeDocuments.value.map((item) => ({
      kind: item.kind,
      title: locale.value === 'fr' ? (item.title_fr || item.title_ar) : (item.title_ar || item.title_fr),
      type: item.kind === 'general_report' ? t('financeAdmin.kindReport') : t('financeAdmin.kindAnnouncement'),
      url: item.file_url,
      href: item.file_url,
      to: item.kind === 'subscriptions_announcement' ? '/subscriptions' : null,
      body: locale.value === 'fr' ? (item.body_fr || item.body_ar || '') : (item.body_ar || item.body_fr || ''),
    }))
  }
  if (isExternal.value && publicDocuments.value.length) {
    return publicDocuments.value.map((item) => ({
      title: locale.value === 'fr' ? (item.title_fr || item.title_ar) : (item.title_ar || item.title_fr),
      type: item.category ? t(`externalRel.categories.${item.category}`) : '',
      url: item.file_url,
      href: item.file_url,
    }))
  }
  return list(secretariat.value?.documents)
})

async function submitContact() {
  if (sending.value) return
  sending.value = true
  contactError.value = ''
  try {
    await submitSecretariatMessage(route.params.slug, {
      sender_name: form.name,
      sender_email: form.email,
      sender_phone: form.phone || undefined,
      subject: form.subject,
      body: form.message,
    })
    sent.value = true
  } catch (e) {
    const errors = e.response?.data?.errors
    contactError.value = errors ? Object.values(errors).flat().join(' ') : e.response?.data?.message || t('pages.contact.error')
  } finally {
    sending.value = false
  }
}

async function loadFeed(slug) {
  try {
    feed.value = await fetchSecretariatFeed(slug)
  } catch {
    feed.value = { news: [], announcements: [], albums: [], events: [], media_center: [], department: null }
  }
  if (slug === 'external-relations') {
    try {
      publicPartners.value = await fetchPublicPartners()
    } catch {
      publicPartners.value = []
    }
    try {
      publicDocuments.value = await fetchPublicDocuments()
    } catch {
      publicDocuments.value = []
    }
  } else {
    publicPartners.value = []
    publicDocuments.value = []
  }
  if (slug === 'finance') {
    try {
      financeDocuments.value = await fetchPublicFinanceDocuments()
    } catch {
      financeDocuments.value = []
    }
  } else {
    financeDocuments.value = []
  }
  if (slug === 'general') {
    try {
      meetingOutputs.value = await fetchPublicMeetingOutputs('general')
    } catch {
      meetingOutputs.value = []
    }
  } else {
    meetingOutputs.value = []
  }
}

watch(
  () => route.params.slug,
  (slug) => {
    if (slug) loadFeed(slug)
  },
  { immediate: true },
)
</script>

<template>
  <div v-if="secretariat">
    <!-- Banner -->
    <section class="relative min-h-[42vh] overflow-hidden">
      <img :src="secretariat.banner" alt="" class="absolute inset-0 h-full w-full object-cover" />
      <div class="absolute inset-0 bg-[linear-gradient(115deg,rgba(18,40,28,0.9),rgba(18,40,28,0.55))]" />
      <div class="relative z-10 mx-auto flex min-h-[42vh] max-w-6xl flex-col justify-end px-5 py-12 md:px-8">
        <p class="text-sm text-[var(--rdp-gold)]">
          <RouterLink to="/secretariats" class="hover:underline">{{ t('nav.secretariats') }}</RouterLink>
        </p>
        <h1 class="mt-2 font-[family-name:var(--font-display)] text-4xl font-bold text-white md:text-5xl">
          {{ t(secretariat.nameKey) }}
        </h1>
        <p class="mt-3 max-w-2xl text-lg text-white/90">
          {{ localized(secretariat.tagline) }}
        </p>
      </div>
    </section>

    <div class="mx-auto max-w-6xl space-y-14 px-5 py-12 md:px-8">
      <!-- Intro + officer / deputy cards -->
      <section class="space-y-6">
        <div>
          <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('secretariat.about') }}</h2>
          <p class="mt-3 leading-relaxed text-slate-700">{{ localized(secretariat.summary) }}</p>
        </div>
        <div v-if="people.length" class="grid gap-4 md:grid-cols-2">
          <aside
            v-for="item in people"
            :key="item.role"
            class="rounded-2xl border border-[var(--rdp-forest)]/15 bg-white p-5"
          >
            <p class="mb-3 text-xs font-semibold tracking-wide text-[var(--rdp-forest)] uppercase">
              {{ t(`secretariat.${item.role}Role`) }}
            </p>
            <div class="flex items-center gap-4">
              <img
                v-if="item.person.photo"
                :src="item.person.photo"
                :alt="localized(item.person.name)"
                class="h-20 w-20 rounded-full object-cover object-top ring-2 ring-[var(--rdp-forest)]/20"
              />
              <div
                v-else
                class="flex h-16 w-16 items-center justify-center rounded-full bg-[var(--rdp-forest)] text-lg font-bold text-white"
              >
                {{ localized(item.person.name).slice(0, 1) }}
              </div>
              <div>
                <p class="font-semibold text-[var(--rdp-ink)]">{{ localized(item.person.name) }}</p>
                <p class="text-sm text-[var(--rdp-forest)]">{{ localized(item.person.title) }}</p>
              </div>
            </div>
            <p class="mt-3 text-sm text-slate-600">{{ localized(item.person.bio) }}</p>
            <p v-if="item.person.email" class="mt-3 text-sm font-medium text-slate-700">
              {{ item.person.email }}
            </p>
          </aside>
        </div>
      </section>

      <!-- Vision / Mission / Objectives -->
      <section class="grid gap-4 md:grid-cols-3">
        <div class="rounded-2xl bg-white p-5 shadow-sm">
          <h3 class="font-semibold text-[var(--rdp-forest)]">{{ t('secretariat.vision') }}</h3>
          <p class="mt-2 text-sm text-slate-700">{{ localized(secretariat.vision) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm">
          <h3 class="font-semibold text-[var(--rdp-forest)]">{{ t('secretariat.mission') }}</h3>
          <p class="mt-2 text-sm text-slate-700">{{ localized(secretariat.mission) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm">
          <h3 class="font-semibold text-[var(--rdp-forest)]">{{ t('secretariat.objectives') }}</h3>
          <ul class="mt-2 list-disc space-y-1 pe-5 text-sm text-slate-700">
            <li v-for="(item, index) in list(secretariat.objectives)" :key="index">{{ item }}</li>
          </ul>
        </div>
      </section>

      <!-- Tasks -->
      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('secretariat.tasks') }}</h2>
        <ul class="mt-4 grid gap-2 sm:grid-cols-2">
          <li
            v-for="(task, index) in list(secretariat.tasks)"
            :key="index"
            class="rounded-lg border border-[var(--rdp-forest)]/10 bg-white px-4 py-3 text-sm text-slate-700"
          >
            {{ task }}
          </li>
        </ul>
      </section>

      <!-- Programs -->
      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('secretariat.programs') }}</h2>
        <div class="mt-4 flex flex-wrap gap-2">
          <span
            v-for="(program, index) in list(secretariat.programs)"
            :key="index"
            class="rounded-full bg-[var(--rdp-forest)]/10 px-4 py-2 text-sm text-[var(--rdp-forest)]"
          >
            {{ program }}
          </span>
        </div>
      </section>

      <!-- Meeting outputs (amanah générale) -->
      <section v-if="isGeneral && meetingOutputs.length" class="space-y-4">
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('secretariat.meetingOutputs') }}</h2>
        <article
          v-for="item in meetingOutputs"
          :key="item.id"
          class="rounded-2xl border border-[var(--rdp-forest)]/15 bg-white p-5 shadow-sm"
        >
          <p class="text-xs text-slate-500">{{ item.meeting_on }}<span v-if="item.location"> · {{ item.location }}</span></p>
          <h3 class="mt-1 text-lg font-semibold text-[var(--rdp-ink)]">
            {{ locale === 'fr' ? (item.title_fr || item.title_ar) : (item.title_ar || item.title_fr) }}
          </h3>
          <p
            v-if="locale === 'fr' ? (item.attendees_fr || item.attendees_ar) : (item.attendees_ar || item.attendees_fr)"
            class="mt-3 whitespace-pre-line text-sm text-slate-700"
          >
            <span class="font-medium text-[var(--rdp-forest)]">{{ t('secretariat.meetingAttendees') }}: </span>
            {{ locale === 'fr' ? (item.attendees_fr || item.attendees_ar) : (item.attendees_ar || item.attendees_fr) }}
          </p>
          <p
            v-if="locale === 'fr' ? (item.agenda_fr || item.agenda_ar) : (item.agenda_ar || item.agenda_fr)"
            class="mt-2 whitespace-pre-line text-sm text-slate-700"
          >
            <span class="font-medium text-[var(--rdp-forest)]">{{ t('secretariat.meetingAgenda') }}: </span>
            {{ locale === 'fr' ? (item.agenda_fr || item.agenda_ar) : (item.agenda_ar || item.agenda_fr) }}
          </p>
          <p
            v-if="locale === 'fr' ? (item.outputs_fr || item.outputs_ar) : (item.outputs_ar || item.outputs_fr)"
            class="mt-2 whitespace-pre-line text-sm text-slate-700"
          >
            <span class="font-medium text-[var(--rdp-forest)]">{{ t('secretariat.meetingConclusions') }}: </span>
            {{ locale === 'fr' ? (item.outputs_fr || item.outputs_ar) : (item.outputs_ar || item.outputs_fr) }}
          </p>
          <p
            v-if="locale === 'fr' ? (item.follow_up_fr || item.follow_up_ar) : (item.follow_up_ar || item.follow_up_fr)"
            class="mt-2 whitespace-pre-line text-sm text-slate-700"
          >
            <span class="font-medium text-[var(--rdp-forest)]">{{ t('secretariat.meetingFollowUp') }}: </span>
            {{ locale === 'fr' ? (item.follow_up_fr || item.follow_up_ar) : (item.follow_up_ar || item.follow_up_fr) }}
          </p>
        </article>
      </section>

      <!-- Finance public documents -->
      <section v-if="isFinance && (financeGeneralReport || financeSubscriptions)" class="space-y-4">
        <article v-if="financeGeneralReport" class="rounded-2xl border border-[var(--rdp-forest)]/15 bg-white p-6 shadow-sm">
          <p class="text-xs font-semibold tracking-wide text-[var(--rdp-forest)] uppercase">{{ t('financeAdmin.kindReport') }}</p>
          <h2 class="mt-2 text-2xl font-semibold text-[var(--rdp-forest)]">
            {{ locale === 'fr' ? (financeGeneralReport.title_fr || financeGeneralReport.title_ar) : (financeGeneralReport.title_ar || financeGeneralReport.title_fr) }}
          </h2>
          <p
            v-if="locale === 'fr' ? (financeGeneralReport.body_fr || financeGeneralReport.body_ar) : (financeGeneralReport.body_ar || financeGeneralReport.body_fr)"
            class="mt-3 whitespace-pre-line text-slate-700"
          >
            {{ locale === 'fr' ? (financeGeneralReport.body_fr || financeGeneralReport.body_ar) : (financeGeneralReport.body_ar || financeGeneralReport.body_fr) }}
          </p>
          <a
            v-if="financeGeneralReport.file_url"
            :href="financeGeneralReport.file_url"
            target="_blank"
            rel="noreferrer"
            class="mt-4 inline-flex rounded bg-[var(--rdp-forest)] px-4 py-2 text-sm font-semibold text-white"
          >
            {{ t('financePublic.viewDocument') }}
          </a>
        </article>
        <article v-if="financeSubscriptions" class="rounded-2xl bg-white p-6 shadow-sm">
          <p class="text-xs font-semibold tracking-wide text-[var(--rdp-forest)] uppercase">{{ t('financeAdmin.kindAnnouncement') }}</p>
          <h3 class="mt-2 text-xl font-semibold text-[var(--rdp-forest)]">
            {{ locale === 'fr' ? (financeSubscriptions.title_fr || financeSubscriptions.title_ar) : (financeSubscriptions.title_ar || financeSubscriptions.title_fr) }}
          </h3>
          <RouterLink
            to="/subscriptions"
            class="mt-4 inline-flex text-sm font-semibold text-[var(--rdp-forest)] hover:underline"
          >
            {{ t('financePublic.openSubscriptions') }}
          </RouterLink>
        </article>
      </section>

      <!-- Academic extras -->
      <section v-if="secretariat.subjects || secretariat.stages || secretariat.showStudentRegister" class="space-y-6">
        <div v-if="secretariat.subjects">
          <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('secretariat.subjects') }}</h2>
          <div class="mt-3 flex flex-wrap gap-2">
            <span
              v-for="(subject, index) in list(secretariat.subjects)"
              :key="index"
              class="rounded bg-white px-3 py-2 text-sm shadow-sm"
            >
              {{ subject }}
            </span>
          </div>
        </div>
        <div v-if="secretariat.stages">
          <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('secretariat.stages') }}</h2>
          <div class="mt-3 flex flex-wrap gap-2">
            <span
              v-for="(stage, index) in list(secretariat.stages)"
              :key="index"
              class="rounded bg-white px-3 py-2 text-sm shadow-sm"
            >
              {{ stage }}
            </span>
          </div>
        </div>
        <RouterLink
          v-if="secretariat.showStudentRegister"
          to="/register/student"
          class="inline-flex rounded bg-[var(--rdp-forest)] px-5 py-3 text-sm font-semibold text-white"
        >
          {{ t('home.ctaStudent') }}
        </RouterLink>
      </section>

      <!-- Women / children programs -->
      <section v-if="secretariat.womenPrograms || secretariat.childrenPrograms" class="grid gap-6 md:grid-cols-2">
        <div v-if="secretariat.womenPrograms" class="rounded-2xl bg-white p-5 shadow-sm">
          <h3 class="font-semibold text-[var(--rdp-forest)]">{{ t('secretariat.womenPrograms') }}</h3>
          <ul class="mt-3 list-disc space-y-1 pe-5 text-sm text-slate-700">
            <li v-for="(item, index) in list(secretariat.womenPrograms)" :key="index">{{ item }}</li>
          </ul>
        </div>
        <div v-if="secretariat.childrenPrograms" class="rounded-2xl bg-white p-5 shadow-sm">
          <h3 class="font-semibold text-[var(--rdp-forest)]">{{ t('secretariat.childrenPrograms') }}</h3>
          <ul class="mt-3 list-disc space-y-1 pe-5 text-sm text-slate-700">
            <li v-for="(item, index) in list(secretariat.childrenPrograms)" :key="index">{{ item }}</li>
          </ul>
        </div>
      </section>

      <!-- Social initiatives -->
      <section v-if="secretariat.initiatives?.length">
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('secretariat.initiatives') }}</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
          <article
            v-for="(initiative, index) in secretariat.initiatives"
            :key="index"
            class="overflow-hidden rounded-xl bg-white shadow-sm"
          >
            <img :src="initiative.image" alt="" class="h-40 w-full object-cover" />
            <div class="space-y-1 p-4">
              <p class="text-xs text-slate-500">{{ initiative.date }} · {{ localized(initiative.status) }}</p>
              <h3 class="font-semibold">{{ localized(initiative.title) }}</h3>
              <p class="text-sm text-slate-600">{{ localized(initiative.summary) }}</p>
            </div>
          </article>
        </div>
        <RouterLink
          v-if="route.params.slug === 'social'"
          to="/help-request"
          class="mt-4 inline-flex rounded bg-[var(--rdp-forest)] px-5 py-3 text-sm font-semibold text-white"
        >
          {{ t('socialHelp.publicTitle') }}
        </RouterLink>
        <RouterLink
          v-if="secretariat.showVolunteer"
          to="/contact"
          class="mt-4 ms-3 inline-flex rounded border border-[var(--rdp-forest)] px-5 py-3 text-sm font-semibold text-[var(--rdp-forest)]"
        >
          {{ t('secretariat.volunteer') }}
        </RouterLink>
      </section>

      <section v-if="isExternal" class="rounded-xl border border-[var(--rdp-forest)]/15 bg-white p-6">
        <h2 class="text-xl font-semibold text-[var(--rdp-forest)]">{{ t('externalRel.publicTitle') }}</h2>
        <p class="mt-2 text-sm text-slate-600">{{ t('externalRel.publicSubtitle') }}</p>
        <RouterLink
          to="/external-contact"
          class="mt-4 inline-flex rounded bg-[var(--rdp-forest)] px-5 py-3 text-sm font-semibold text-white"
        >
          {{ t('externalRel.submit') }}
        </RouterLink>
      </section>

      <!-- Partners -->
      <section v-if="displayedPartners.length">
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('secretariat.partners') }}</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
          <div
            v-for="(partner, index) in displayedPartners"
            :key="index"
            class="rounded-xl border border-[var(--rdp-forest)]/10 bg-white p-4"
          >
            <p class="font-semibold">{{ partner.name }}</p>
            <p v-if="partner.type" class="mt-1 text-xs text-[var(--rdp-gold)]">{{ t(`externalRel.types.${partner.type}`) }}</p>
            <p v-if="partner.desc" class="mt-1 text-sm text-slate-600">{{ partner.desc }}</p>
            <a
              v-if="partner.website"
              :href="partner.website"
              target="_blank"
              rel="noreferrer"
              class="mt-2 inline-flex text-sm font-semibold text-[var(--rdp-forest)] hover:underline"
            >
              {{ partner.website }}
            </a>
          </div>
        </div>
      </section>

      <!-- Public stats -->
      <section v-if="secretariat.stats?.length">
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('secretariat.publicStats') }}</h2>
        <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4">
          <div
            v-for="stat in secretariat.stats"
            :key="stat.key"
            class="rounded-xl bg-white px-4 py-5 text-center shadow-sm"
          >
            <p class="text-2xl font-bold text-[var(--rdp-forest)]">{{ stat.value }}</p>
            <p class="mt-1 text-sm text-slate-600">{{ t(`secretariat.statLabels.${stat.key}`) }}</p>
          </div>
        </div>
        <p v-if="secretariat.chartsNote" class="mt-3 text-sm text-slate-500">
          {{ localized(secretariat.chartsNote) }}
        </p>
      </section>

      <!-- Documents -->
      <section v-if="displayedDocuments.length">
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('secretariat.documents') }}</h2>
        <ul class="mt-4 space-y-2">
          <li
            v-for="(doc, index) in displayedDocuments"
            :key="doc.kind || index"
            class="rounded-lg bg-white px-4 py-3 text-sm shadow-sm"
          >
            <RouterLink
              v-if="doc.to"
              :to="doc.to"
              class="font-medium text-[var(--rdp-forest)] hover:underline"
            >
              {{ doc.title }}
            </RouterLink>
            <a
              v-else-if="doc.href"
              :href="doc.href"
              target="_blank"
              rel="noreferrer"
              class="font-medium text-[var(--rdp-forest)] hover:underline"
            >
              {{ doc.title }}
            </a>
            <span v-else class="font-medium text-[var(--rdp-forest)]">{{ doc.title }}</span>
            <span v-if="doc.type" class="text-slate-500"> — {{ doc.type }}</span>
          </li>
        </ul>
      </section>

      <!-- Social links (media) -->
      <section v-if="secretariat.socialLinks?.length">
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('secretariat.social') }}</h2>
        <div class="mt-4 flex flex-wrap gap-3">
          <a
            v-for="link in secretariat.socialLinks"
            :key="link.label"
            :href="link.url"
            class="rounded bg-[var(--rdp-forest)] px-4 py-2 text-sm text-white"
          >
            {{ link.label }}
          </a>
        </div>
        <RouterLink to="/gallery" class="mt-4 inline-flex text-sm font-semibold text-[var(--rdp-forest)] hover:underline">
          {{ t('home.galleryCta') }}
        </RouterLink>
      </section>

      <!-- Announcements (API) -->
      <section v-if="announcements.length">
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('secretariatAdmin.announcements') }}</h2>
        <div class="mt-4 grid gap-3 md:grid-cols-2">
          <article
            v-for="item in announcements"
            :key="item.id"
            class="overflow-hidden rounded-xl border border-[var(--rdp-forest)]/10 bg-white"
          >
            <img
              :src="item.image_url || '/logo.png'"
              alt=""
              class="h-40 w-full object-cover"
            />
            <div class="p-4">
              <h3 class="font-semibold">{{ locale === 'ar' ? item.title_ar : (item.title_en || item.title_fr) }}</h3>
              <p class="mt-2 text-sm text-slate-600">
                {{ locale === 'ar' ? item.content_ar : (item.content_en || item.content_fr) }}
              </p>
            </div>
          </article>
        </div>
      </section>

      <!-- Media center -->
      <section v-if="route.params.slug === 'media'" class="space-y-4">
        <div class="flex flex-wrap items-end justify-between gap-3">
          <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">📢 {{ t('mediaCenter.publicTitle') }}</h2>
          <RouterLink to="/media-center" class="text-sm font-semibold text-[var(--rdp-forest)] hover:underline">
            {{ t('home.readMore') }}
          </RouterLink>
        </div>
        <div class="flex flex-wrap gap-2">
          <RouterLink
            v-for="kind in ['official', 'statement', 'coverage', 'conference', 'interview']"
            :key="kind"
            :to="`/media-center?kind=${kind}`"
            class="rounded-full bg-white px-4 py-2 text-sm text-[var(--rdp-forest)] shadow-sm"
          >
            {{ t(`mediaCenter.kinds.${kind}`) }}
          </RouterLink>
        </div>
        <div v-if="mediaCenter.length" class="grid gap-4 md:grid-cols-3">
          <article v-for="item in mediaCenter" :key="item.id" class="overflow-hidden rounded-xl bg-white shadow-sm">
            <img :src="item.image_url || '/logo.png'" alt="" class="h-36 w-full object-cover" />
            <div class="space-y-1 p-4">
              <p class="text-xs font-semibold text-[var(--rdp-gold)]">{{ t(`mediaCenter.kinds.${item.kind}`) }}</p>
              <p class="text-xs text-slate-500">{{ item.occurred_on || (item.published_at || '').slice(0, 10) }}</p>
              <h3 class="font-semibold">{{ locale === 'ar' ? item.title_ar : item.title_fr }}</h3>
              <RouterLink :to="`/media-center/${item.slug}`" class="text-sm font-semibold text-[var(--rdp-forest)] hover:underline">
                {{ t('home.readMore') }}
              </RouterLink>
            </div>
          </article>
        </div>
      </section>

      <!-- News -->
      <section v-if="news.length">
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('secretariat.news') }}</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-3">
          <article v-for="item in news" :key="item.id" class="overflow-hidden rounded-xl bg-white shadow-sm">
            <img :src="item.image" alt="" class="h-36 w-full object-cover" />
            <div class="space-y-1 p-4">
              <p class="text-xs text-slate-500">{{ item.date }}</p>
              <h3 class="font-semibold">{{ localized(item.title) }}</h3>
              <RouterLink :to="`/news/${item.slug}`" class="text-sm font-semibold text-[var(--rdp-forest)] hover:underline">
                {{ t('home.readMore') }}
              </RouterLink>
            </div>
          </article>
        </div>
      </section>

      <!-- Events -->
      <section v-if="events.length">
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">
          {{ isExternal ? t('externalRel.eventsTitle') : t('secretariat.events') }}
        </h2>
        <div class="mt-4 grid gap-4 md:grid-cols-3">
          <article v-for="event in events" :key="event.id" class="overflow-hidden rounded-xl bg-white shadow-sm">
            <img :src="event.image || '/logo.png'" alt="" class="h-36 w-full object-cover" />
            <div class="space-y-1 p-4">
              <p v-if="event.type" class="text-xs font-semibold text-[var(--rdp-gold)]">
                {{ t(`secretariat.eventTypes.${event.type}`) }}
              </p>
              <p class="text-xs text-slate-500">
                {{ event.date }}<span v-if="event.time"> · {{ event.time }}</span>
              </p>
              <h3 class="font-semibold">{{ localized(event.title) }}</h3>
              <p v-if="localized(event.summary)" class="line-clamp-2 text-sm text-slate-700">{{ localized(event.summary) }}</p>
              <EventStarRating
                v-if="canRateEvents && event.slug && event.id"
                class="pt-2"
                :slug="event.slug"
                :event-id="event.id"
                :average="event.rating_avg"
                :count="event.rating_count"
              />
              <RouterLink :to="`/events/${event.slug}`" class="text-sm font-semibold text-[var(--rdp-forest)] hover:underline">
                {{ t('home.eventDetails') }}
              </RouterLink>
            </div>
          </article>
        </div>
      </section>

      <!-- Gallery carousel -->
      <PhotoGallerySection
        v-if="albums.length"
        :title="t('nav.gallery')"
        :albums="albums"
        more-to="/gallery"
      />

      <!-- Contact form -->
      <section id="contact" class="rounded-2xl border border-[var(--rdp-forest)]/15 bg-white p-6">
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">
          {{ localized(secretariat.contactLabel) }}
        </h2>
        <p class="mt-2 text-sm text-slate-600">{{ t('secretariat.contactHint') }}</p>

        <p v-if="sent" class="mt-4 text-sm text-teal-800">{{ t('secretariat.contactSuccess') }}</p>
        <form v-else class="mt-4 grid gap-3 md:grid-cols-2" @submit.prevent="submitContact">
          <p v-if="contactError" class="rounded border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700 md:col-span-2">{{ contactError }}</p>
          <input v-model="form.name" required :placeholder="t('forms.name')" class="rounded border border-slate-300 px-3 py-2" />
          <input v-model="form.email" required type="email" :placeholder="t('forms.email')" class="rounded border border-slate-300 px-3 py-2" />
          <input v-model="form.phone" :placeholder="t('forms.phoneOptional')" class="rounded border border-slate-300 px-3 py-2 md:col-span-2" />
          <input v-model="form.subject" required :placeholder="t('forms.subject')" class="rounded border border-slate-300 px-3 py-2 md:col-span-2" />
          <textarea v-model="form.message" required rows="4" :placeholder="t('forms.message')" class="rounded border border-slate-300 px-3 py-2 md:col-span-2" />
          <button type="submit" class="rounded bg-[var(--rdp-forest)] px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50 md:col-span-2 md:w-fit" :disabled="sending">
            {{ sending ? t('pages.contact.sending') : t('forms.send') }}
          </button>
        </form>
      </section>
    </div>
  </div>

  <div v-else class="mx-auto max-w-3xl px-5 py-20">
    <p>{{ t('pages.notFound') }}</p>
    <RouterLink to="/secretariats" class="mt-4 inline-flex text-[var(--rdp-forest)] hover:underline">
      {{ t('nav.secretariats') }}
    </RouterLink>
  </div>
</template>
