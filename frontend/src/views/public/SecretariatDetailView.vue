<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { RouterLink, useRoute } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { getSecretariat } from '@/data/secretariats'
import {
  albumsBySecretariat,
  eventsBySecretariat,
  newsBySecretariat,
} from '@/data/publicContent'
import { fetchSecretariatFeed } from '@/services/content'

const route = useRoute()
const { t, locale } = useI18n()
const sent = ref(false)
const feed = ref({ news: [], announcements: [], albums: [] })

const form = reactive({
  name: '',
  email: '',
  subject: '',
  message: '',
})

const secretariat = computed(() => getSecretariat(route.params.slug))

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

const announcements = computed(() => feed.value.announcements || [])

const events = computed(() => eventsBySecretariat(route.params.slug))

const albums = computed(() => {
  if (feed.value.albums?.length) {
    return feed.value.albums.map((item) => ({
      id: item.id,
      slug: String(item.id),
      cover: item.cover_url || item.media?.[0]?.url || '/logo.png',
      title: { ar: item.title_ar, fr: item.title_fr },
    }))
  }
  return albumsBySecretariat(route.params.slug)
})

const localized = (value) => value?.[locale.value] || value?.fr || value?.ar || ''
const list = (value) => {
  const items = value?.[locale.value] || value?.fr || value?.ar || []
  return Array.isArray(items) ? items : []
}

function submitContact() {
  sent.value = true
}

async function loadFeed(slug) {
  try {
    feed.value = await fetchSecretariatFeed(slug)
  } catch {
    feed.value = { news: [], announcements: [], albums: [] }
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
      <!-- Intro + Officer -->
      <section class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
        <div>
          <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('secretariat.about') }}</h2>
          <p class="mt-3 leading-relaxed text-slate-700">{{ localized(secretariat.summary) }}</p>
        </div>
        <aside class="rounded-2xl border border-[var(--rdp-forest)]/15 bg-white p-5">
          <div class="flex items-center gap-4">
            <img
              v-if="secretariat.officer.photo"
              :src="secretariat.officer.photo"
              :alt="localized(secretariat.officer.name)"
              class="h-20 w-20 rounded-full object-cover object-top ring-2 ring-[var(--rdp-forest)]/20"
            />
            <div
              v-else
              class="flex h-16 w-16 items-center justify-center rounded-full bg-[var(--rdp-forest)] text-lg font-bold text-white"
            >
              {{ localized(secretariat.officer.name).slice(0, 1) }}
            </div>
            <div>
              <p class="font-semibold text-[var(--rdp-ink)]">{{ localized(secretariat.officer.name) }}</p>
              <p class="text-sm text-[var(--rdp-forest)]">{{ localized(secretariat.officer.title) }}</p>
            </div>
          </div>
          <p class="mt-3 text-sm text-slate-600">{{ localized(secretariat.officer.bio) }}</p>
          <p v-if="secretariat.officer.email" class="mt-3 text-sm font-medium text-slate-700">
            {{ secretariat.officer.email }}
          </p>
        </aside>
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
          v-if="secretariat.showVolunteer"
          to="/contact"
          class="mt-4 inline-flex rounded border border-[var(--rdp-forest)] px-5 py-3 text-sm font-semibold text-[var(--rdp-forest)]"
        >
          {{ t('secretariat.volunteer') }}
        </RouterLink>
      </section>

      <!-- Partners -->
      <section v-if="secretariat.partners">
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('secretariat.partners') }}</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2">
          <div
            v-for="(partner, index) in list(secretariat.partners)"
            :key="index"
            class="rounded-xl border border-[var(--rdp-forest)]/10 bg-white p-4"
          >
            <p class="font-semibold">{{ partner.name }}</p>
            <p class="mt-1 text-sm text-slate-600">{{ partner.desc }}</p>
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
      <section v-if="secretariat.documents">
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('secretariat.documents') }}</h2>
        <ul class="mt-4 space-y-2">
          <li
            v-for="(doc, index) in list(secretariat.documents)"
            :key="index"
            class="rounded-lg bg-white px-4 py-3 text-sm shadow-sm"
          >
            <span class="font-medium">{{ doc.title }}</span>
            <span class="text-slate-500"> — {{ doc.type }}</span>
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
            class="rounded-xl border border-[var(--rdp-forest)]/10 bg-white p-4"
          >
            <h3 class="font-semibold">{{ locale === 'ar' ? item.title_ar : item.title_fr }}</h3>
            <p class="mt-2 text-sm text-slate-600">
              {{ locale === 'ar' ? item.content_ar : item.content_fr }}
            </p>
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
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('secretariat.events') }}</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
          <article v-for="event in events" :key="event.id" class="rounded-xl bg-white p-5 shadow-sm">
            <h3 class="font-semibold">{{ localized(event.title) }}</h3>
            <p class="mt-1 text-sm text-slate-500">{{ event.date }} · {{ event.time }}</p>
            <p class="mt-2 text-sm text-slate-700">{{ localized(event.summary) }}</p>
            <div class="mt-3 flex flex-wrap gap-3">
              <RouterLink :to="`/events/${event.slug}`" class="text-sm font-semibold text-[var(--rdp-forest)] hover:underline">
                {{ t('home.eventDetails') }}
              </RouterLink>
              <RouterLink
                v-if="event.registrationOpen || secretariat.showActivityRegister"
                :to="`/events/${event.slug}#register`"
                class="text-sm font-semibold text-slate-700 hover:underline"
              >
                {{ t('secretariat.registerActivity') }}
              </RouterLink>
            </div>
          </article>
        </div>
      </section>

      <!-- Gallery -->
      <section v-if="albums.length">
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('nav.gallery') }}</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <RouterLink
            v-for="album in albums"
            :key="album.slug"
            :to="`/gallery/${album.slug}`"
            class="overflow-hidden rounded-xl"
          >
            <img :src="album.cover" alt="" class="h-40 w-full object-cover" />
            <p class="mt-2 text-sm font-medium text-[var(--rdp-forest)]">{{ localized(album.title) }}</p>
          </RouterLink>
        </div>
      </section>

      <!-- Contact form -->
      <section id="contact" class="rounded-2xl border border-[var(--rdp-forest)]/15 bg-white p-6">
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">
          {{ localized(secretariat.contactLabel) }}
        </h2>
        <p class="mt-2 text-sm text-slate-600">{{ t('secretariat.contactHint') }}</p>

        <p v-if="sent" class="mt-4 text-sm text-teal-800">{{ t('pages.contact.success') }}</p>
        <form v-else class="mt-4 grid gap-3 md:grid-cols-2" @submit.prevent="submitContact">
          <input v-model="form.name" required :placeholder="t('forms.name')" class="rounded border border-slate-300 px-3 py-2" />
          <input v-model="form.email" required type="email" :placeholder="t('forms.email')" class="rounded border border-slate-300 px-3 py-2" />
          <input v-model="form.subject" required :placeholder="t('forms.subject')" class="rounded border border-slate-300 px-3 py-2 md:col-span-2" />
          <textarea v-model="form.message" required rows="4" :placeholder="t('forms.message')" class="rounded border border-slate-300 px-3 py-2 md:col-span-2" />
          <button type="submit" class="rounded bg-[var(--rdp-forest)] px-5 py-2.5 text-sm font-semibold text-white md:col-span-2 md:w-fit">
            {{ t('forms.send') }}
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
