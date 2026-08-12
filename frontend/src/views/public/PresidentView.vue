<script setup>
import { reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { presidentPage as page } from '@/data/presidentPage'

const { t, locale } = useI18n()
const contactSent = ref(false)
const form = reactive({
  name: '',
  email: '',
  subject: '',
  message: '',
})

const localized = (value) => value?.[locale.value] || value?.fr || value?.ar || ''
const list = (value) => {
  const items = value?.[locale.value] || value?.fr || value?.ar || []
  return Array.isArray(items) ? items : []
}

function submitContact() {
  contactSent.value = true
}
</script>

<template>
  <div>
    <section class="relative min-h-[48vh] overflow-hidden">
      <img :src="page.banner" alt="" class="absolute inset-0 h-full w-full object-cover" />
      <div class="absolute inset-0 bg-[linear-gradient(115deg,rgba(18,40,28,0.92),rgba(18,40,28,0.55))]" />
      <div class="relative z-10 mx-auto flex min-h-[48vh] max-w-6xl flex-col justify-end gap-4 px-5 py-12 md:px-8">
        <img
          src="/logo.png"
          :alt="t('app.name')"
          class="h-16 w-auto self-start rounded-md bg-white object-contain px-2 py-1 shadow"
        />
        <p class="text-sm font-medium tracking-wide text-white/80">{{ t('app.name') }}</p>
        <h1 class="font-[family-name:var(--font-display)] text-4xl font-bold text-white md:text-5xl">
          {{ t('org.president') }}
        </h1>
        <p class="max-w-3xl text-lg text-[var(--rdp-gold)]">{{ localized(page.tagline) }}</p>
        <p class="max-w-3xl text-base text-white/85">{{ localized(page.subtitle) }}</p>
      </div>
    </section>

    <div class="mx-auto max-w-6xl space-y-16 px-5 py-12 md:px-8">
      <!-- Profil -->
      <section class="grid items-start gap-8 md:grid-cols-[220px_1fr]">
        <div class="mx-auto w-full max-w-[220px]">
          <div
            v-if="page.profile.photo"
            class="aspect-[3/4] overflow-hidden rounded-2xl bg-[var(--rdp-forest)]/10"
          >
            <img
              :src="page.profile.photo"
              :alt="localized(page.profile.name)"
              class="h-full w-full object-cover"
            />
          </div>
          <div
            v-else
            class="flex aspect-[3/4] flex-col items-center justify-center rounded-2xl bg-[var(--rdp-forest)]/10 px-4 text-center"
          >
            <img src="/logo.png" alt="" class="h-16 w-auto object-contain opacity-80" />
            <p class="mt-4 text-xs text-slate-600">{{ localized(page.profile.photoNote) }}</p>
          </div>
        </div>
        <div>
          <p class="text-sm font-semibold tracking-wide text-[var(--rdp-forest)] uppercase">
            {{ localized(page.profile.title) }}
          </p>
          <h2 class="mt-2 font-[family-name:var(--font-display)] text-3xl font-bold text-[var(--rdp-ink)]">
            {{ localized(page.profile.name) }}
          </h2>
          <p class="mt-2 text-sm text-slate-500">{{ localized(page.profile.mandate) }}</p>
          <p class="mt-5 max-w-3xl leading-relaxed text-slate-700">
            {{ localized(page.profile.bio) }}
          </p>
        </div>
      </section>

      <!-- Mot du président -->
      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('president.message') }}</h2>
        <div class="mt-5 max-w-4xl whitespace-pre-line leading-relaxed text-slate-700">
          {{ localized(page.message) }}
        </div>
      </section>

      <!-- Vision -->
      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('president.vision') }}</h2>
        <ul class="mt-5 grid gap-3 sm:grid-cols-2">
          <li
            v-for="(item, index) in list(page.visionItems)"
            :key="index"
            class="border-s-2 border-[var(--rdp-forest)]/30 bg-white/70 px-4 py-3 text-sm text-slate-700"
          >
            {{ item }}
          </li>
        </ul>
      </section>

      <!-- Missions -->
      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('president.missions') }}</h2>
        <p class="mt-2 text-sm text-slate-500">{{ localized(page.missions.note) }}</p>
        <ul class="mt-5 grid gap-2 sm:grid-cols-2">
          <li
            v-for="(item, index) in list(page.missions)"
            :key="index"
            class="rounded-lg bg-white px-4 py-3 text-sm text-slate-700 shadow-sm"
          >
            {{ item }}
          </li>
        </ul>
      </section>

      <!-- Priorités -->
      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('president.priorities') }}</h2>
        <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <article
            v-for="item in page.priorities"
            :key="item.key"
            class="border-t-2 border-[var(--rdp-forest)] bg-white p-5 shadow-sm"
          >
            <h3 class="font-semibold text-[var(--rdp-forest)]">{{ localized(item.title) }}</h3>
            <p class="mt-2 text-sm text-slate-600">{{ localized(item.text) }}</p>
          </article>
        </div>
      </section>

      <!-- Messages ciblés -->
      <section class="grid gap-6 md:grid-cols-3">
        <article>
          <h2 class="text-xl font-semibold text-[var(--rdp-forest)]">{{ t('president.toCommunity') }}</h2>
          <p class="mt-3 text-sm leading-relaxed text-slate-700">{{ localized(page.messageCommunity) }}</p>
        </article>
        <article>
          <h2 class="text-xl font-semibold text-[var(--rdp-forest)]">{{ t('president.toParents') }}</h2>
          <p class="mt-3 text-sm leading-relaxed text-slate-700">{{ localized(page.messageParents) }}</p>
          <RouterLink
            to="/parents-council"
            class="mt-3 inline-flex text-sm font-medium text-[var(--rdp-forest)] underline-offset-4 hover:underline"
          >
            {{ t('nav.parents') }}
          </RouterLink>
        </article>
        <article>
          <h2 class="text-xl font-semibold text-[var(--rdp-forest)]">{{ t('president.toYouth') }}</h2>
          <p class="mt-3 text-sm leading-relaxed text-slate-700">{{ localized(page.messageYouth) }}</p>
        </article>
      </section>

      <!-- Initiatives -->
      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('president.initiatives') }}</h2>
        <div class="mt-4 flex flex-wrap gap-2">
          <span
            v-for="(item, index) in list(page.initiatives)"
            :key="index"
            class="bg-[var(--rdp-forest)]/8 px-3 py-1.5 text-sm text-[var(--rdp-forest)]"
          >
            {{ item }}
          </span>
        </div>
      </section>

      <!-- Actualités -->
      <section>
        <div class="flex flex-wrap items-end justify-between gap-3">
          <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('president.news') }}</h2>
          <RouterLink to="/news" class="text-sm font-medium text-[var(--rdp-forest)] hover:underline">
            {{ t('home.seeAll') }}
          </RouterLink>
        </div>
        <div class="mt-5 grid gap-4 md:grid-cols-3">
          <article
            v-for="item in page.news"
            :key="item.slug"
            class="bg-white p-5 shadow-sm"
          >
            <p class="text-xs tracking-wide text-slate-500 uppercase">{{ item.date }}</p>
            <h3 class="mt-2 font-semibold text-[var(--rdp-ink)]">{{ localized(item.title) }}</h3>
            <p class="mt-2 text-sm text-slate-600">{{ localized(item.text) }}</p>
          </article>
        </div>
      </section>

      <!-- Rencontres -->
      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('president.meetings') }}</h2>
        <div class="mt-5 space-y-4">
          <article
            v-for="(item, index) in page.meetings"
            :key="index"
            class="grid gap-2 border-s-2 border-[var(--rdp-gold)] bg-white/80 py-4 ps-5 md:grid-cols-[8rem_1fr]"
          >
            <p class="text-sm font-medium text-[var(--rdp-forest)]">{{ item.date }}</p>
            <div>
              <h3 class="font-semibold">{{ localized(item.title) }}</h3>
              <p class="mt-1 text-sm text-slate-500">
                {{ localized(item.place) }} · {{ localized(item.partner) }}
              </p>
              <p class="mt-2 text-sm text-slate-700">{{ localized(item.text) }}</p>
            </div>
          </article>
        </div>
      </section>

      <!-- Partenariats -->
      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('president.partnerships') }}</h2>
        <p class="mt-2 text-sm text-slate-500">{{ localized(page.partnerships.note) }}</p>
        <ul class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
          <li
            v-for="(item, index) in list(page.partnerships)"
            :key="index"
            class="bg-white px-4 py-3 text-sm text-slate-700 shadow-sm"
          >
            {{ item }}
          </li>
        </ul>
      </section>

      <!-- Réalisations -->
      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('president.achievements') }}</h2>
        <ul class="mt-4 grid gap-2 sm:grid-cols-2">
          <li
            v-for="(item, index) in list(page.achievements)"
            :key="index"
            class="border border-[var(--rdp-forest)]/15 bg-white px-4 py-3 text-sm text-slate-700"
          >
            {{ item }}
          </li>
        </ul>
      </section>

      <!-- Chiffres -->
      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('president.stats') }}</h2>
        <p class="mt-2 text-sm text-slate-500">{{ t('president.statsNote') }}</p>
        <div class="mt-5 grid grid-cols-2 gap-4 md:grid-cols-5">
          <div
            v-for="stat in page.stats"
            :key="stat.key"
            class="bg-[var(--rdp-forest)] px-4 py-5 text-center text-white"
          >
            <p class="text-2xl font-bold">{{ stat.value }}</p>
            <p class="mt-1 text-xs text-white/80">{{ localized(stat.label) }}</p>
          </div>
        </div>
      </section>

      <!-- Contact institutionnel -->
      <section class="bg-[var(--rdp-forest)] px-6 py-10 text-white md:px-10">
        <h2 class="text-2xl font-semibold">{{ t('president.contact') }}</h2>
        <p class="mt-2 max-w-2xl text-sm text-white/85">{{ t('president.contactText') }}</p>

        <p v-if="contactSent" class="mt-6 text-sm text-[var(--rdp-gold)]">
          {{ t('pages.contact.success') }}
        </p>
        <form v-else class="mt-6 grid max-w-2xl gap-3" @submit.prevent="submitContact">
          <input
            v-model="form.name"
            required
            :placeholder="t('forms.name')"
            class="rounded-md border-0 bg-white/95 px-3 py-2 text-[var(--rdp-ink)]"
          />
          <input
            v-model="form.email"
            type="email"
            required
            :placeholder="t('auth.email')"
            class="rounded-md border-0 bg-white/95 px-3 py-2 text-[var(--rdp-ink)]"
          />
          <input
            v-model="form.subject"
            required
            :placeholder="t('forms.subject')"
            class="rounded-md border-0 bg-white/95 px-3 py-2 text-[var(--rdp-ink)]"
          />
          <textarea
            v-model="form.message"
            required
            rows="4"
            :placeholder="t('forms.message')"
            class="rounded-md border-0 bg-white/95 px-3 py-2 text-[var(--rdp-ink)]"
          />
          <div class="flex flex-wrap gap-3">
            <button
              type="submit"
              class="rounded-md bg-[var(--rdp-gold)] px-5 py-2.5 text-sm font-semibold text-[var(--rdp-forest)]"
            >
              {{ t('forms.send') }}
            </button>
            <RouterLink
              to="/contact"
              class="rounded-md border border-white/40 px-5 py-2.5 text-sm text-white hover:bg-white/10"
            >
              {{ t('nav.contact') }}
            </RouterLink>
          </div>
        </form>
      </section>
    </div>
  </div>
</template>
