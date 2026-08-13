<script setup>
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { aboutPage } from '@/data/aboutPage'

const { t, locale } = useI18n()
const localized = (value) => value?.[locale.value] || value?.en || value?.fr || value?.ar || ''
const list = (value) => {
  const items = value?.[locale.value] || value?.en || value?.fr || value?.ar || []
  return Array.isArray(items) ? items : []
}
</script>

<template>
  <div>
    <section class="relative min-h-[48vh] overflow-hidden">
      <img :src="aboutPage.banner" alt="" class="absolute inset-0 h-full w-full object-cover" />
      <div class="absolute inset-0 bg-[linear-gradient(115deg,rgba(18,40,28,0.92),rgba(18,40,28,0.55))]" />
      <div class="relative z-10 mx-auto flex min-h-[48vh] max-w-6xl flex-col justify-end gap-4 px-5 py-12 md:px-8">
        <img
          src="/logo.png"
          :alt="t('app.name')"
          class="h-16 w-auto self-start rounded-md bg-white object-contain px-2 py-1 shadow"
        />
        <h1 class="font-[family-name:var(--font-display)] text-4xl font-bold text-white md:text-5xl">
          {{ t('nav.about') }}
        </h1>
        <p class="max-w-3xl text-lg text-[var(--rdp-gold)]">{{ localized(aboutPage.tagline) }}</p>
        <p class="max-w-3xl text-base text-white/85">{{ localized(aboutPage.subtitle) }}</p>
      </div>
    </section>

    <div class="mx-auto max-w-6xl space-y-16 px-5 py-12 md:px-8">
      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('about.introTitle') }}</h2>
        <p class="mt-3 max-w-4xl leading-relaxed text-slate-700">{{ localized(aboutPage.intro) }}</p>
      </section>

      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('about.history') }}</h2>
        <div class="mt-6 space-y-4 border-s-2 border-[var(--rdp-forest)]/25 ps-5">
          <article
            v-for="(item, index) in aboutPage.timeline"
            :key="index"
            class="relative rounded-xl bg-white p-4 shadow-sm"
          >
            <span class="absolute -start-[1.55rem] top-5 h-3 w-3 rounded-full bg-[var(--rdp-forest)]" />
            <p class="text-xs font-semibold tracking-wide text-[var(--rdp-forest)] uppercase">{{ item.year }}</p>
            <h3 class="mt-1 font-semibold">{{ localized(item.title) }}</h3>
            <p class="mt-1 text-sm text-slate-600">{{ localized(item.text) }}</p>
          </article>
        </div>
      </section>

      <section class="grid gap-4 md:grid-cols-2">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
          <h2 class="text-xl font-semibold text-[var(--rdp-forest)]">{{ t('about.vision') }}</h2>
          <p class="mt-3 text-sm leading-relaxed text-slate-700">{{ localized(aboutPage.vision) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-6 shadow-sm">
          <h2 class="text-xl font-semibold text-[var(--rdp-forest)]">{{ t('about.mission') }}</h2>
          <p class="mt-3 text-sm leading-relaxed text-slate-700">{{ localized(aboutPage.mission) }}</p>
        </div>
      </section>

      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('about.goals') }}</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <article
            v-for="goal in aboutPage.goals"
            :key="goal.key"
            class="rounded-2xl border border-[var(--rdp-forest)]/10 bg-white p-5"
          >
            <h3 class="font-semibold text-[var(--rdp-forest)]">{{ localized(goal.title) }}</h3>
            <p class="mt-2 text-sm text-slate-600">{{ localized(goal.text) }}</p>
          </article>
        </div>
      </section>

      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('about.values') }}</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <article
            v-for="value in aboutPage.values"
            :key="value.key"
            class="rounded-xl bg-[var(--rdp-forest)]/5 p-4"
          >
            <h3 class="font-semibold text-[var(--rdp-forest)]">{{ localized(value.title) }}</h3>
            <p class="mt-2 text-sm text-slate-600">{{ localized(value.text) }}</p>
          </article>
        </div>
      </section>

      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('about.domains') }}</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          <RouterLink
            v-for="domain in aboutPage.domains"
            :key="domain.key"
            :to="domain.path"
            class="rounded-2xl bg-white p-5 shadow-sm transition hover:shadow-md"
          >
            <h3 class="font-semibold text-[var(--rdp-forest)]">{{ localized(domain.title) }}</h3>
            <ul class="mt-3 list-disc space-y-1 pe-5 text-sm text-slate-600">
              <li v-for="(item, index) in list(domain.items)" :key="index">{{ item }}</li>
            </ul>
          </RouterLink>
        </div>
      </section>

      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('about.organization') }}</h2>
        <div class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
          <RouterLink
            v-for="item in aboutPage.orgLinks"
            :key="item.path"
            :to="item.path"
            class="rounded-lg border border-[var(--rdp-forest)]/15 bg-white px-4 py-3 text-sm font-medium text-[var(--rdp-forest)] hover:border-[var(--rdp-forest)]/40"
          >
            {{ t(item.nameKey) }}
          </RouterLink>
        </div>
      </section>

      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('nav.secretariats') }}</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <RouterLink
            v-for="item in aboutPage.secretariats"
            :key="item.path"
            :to="item.path"
            class="rounded-2xl bg-white p-5 shadow-sm"
          >
            <h3 class="font-semibold text-[var(--rdp-forest)]">{{ t(item.nameKey) }}</h3>
            <p class="mt-2 text-sm text-slate-600">{{ localized(item.text) }}</p>
            <span class="mt-3 inline-flex text-sm font-semibold text-[var(--rdp-forest)]">
              {{ t('pages.secretariats.learnMore') }}
            </span>
          </RouterLink>
        </div>
      </section>

      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('about.councils') }}</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
          <RouterLink
            v-for="item in aboutPage.councils"
            :key="item.path"
            :to="item.path"
            class="rounded-2xl border border-[var(--rdp-forest)]/15 bg-white p-6"
          >
            <h3 class="text-xl font-semibold text-[var(--rdp-forest)]">{{ t(item.nameKey) }}</h3>
            <p class="mt-2 text-sm text-slate-600">{{ localized(item.text) }}</p>
          </RouterLink>
        </div>
      </section>

      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('about.achievements') }}</h2>
        <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4">
          <div
            v-for="item in aboutPage.achievements"
            :key="item.key"
            class="rounded-xl bg-white px-4 py-6 text-center shadow-sm"
          >
            <p class="text-3xl font-bold text-[var(--rdp-forest)]">{{ item.value }}</p>
            <p class="mt-2 text-sm text-slate-600">{{ t(`about.achievementLabels.${item.key}`) }}</p>
          </div>
        </div>
      </section>

      <section>
        <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
          <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('about.activities') }}</h2>
          <RouterLink to="/events" class="text-sm font-semibold text-[var(--rdp-forest)] hover:underline">
            {{ t('about.exploreActivities') }}
          </RouterLink>
        </div>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
          <div
            v-for="activity in aboutPage.activities"
            :key="activity.key"
            class="overflow-hidden rounded-xl"
          >
            <img :src="activity.image" alt="" class="h-40 w-full object-cover" />
            <p class="bg-[var(--rdp-forest)] px-4 py-3 text-sm font-medium text-white">
              {{ t(`about.activityLabels.${activity.key}`) }}
            </p>
          </div>
        </div>
      </section>

      <section class="grid gap-4 md:grid-cols-2">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
          <h2 class="text-xl font-semibold text-[var(--rdp-forest)]">{{ t('about.volunteer') }}</h2>
          <p class="mt-3 text-sm text-slate-700">{{ localized(aboutPage.volunteer) }}</p>
          <RouterLink
            to="/contact"
            class="mt-4 inline-flex rounded bg-[var(--rdp-forest)] px-4 py-2 text-sm font-semibold text-white"
          >
            {{ t('about.joinVolunteer') }}
          </RouterLink>
        </div>
        <div class="rounded-2xl bg-white p-6 shadow-sm">
          <h2 class="text-xl font-semibold text-[var(--rdp-forest)]">{{ t('about.membership') }}</h2>
          <p class="mt-3 text-sm text-slate-700">{{ localized(aboutPage.membership) }}</p>
          <RouterLink
            to="/register/member"
            class="mt-4 inline-flex rounded border border-[var(--rdp-forest)] px-4 py-2 text-sm font-semibold text-[var(--rdp-forest)]"
          >
            {{ t('home.ctaMember') }}
          </RouterLink>
        </div>
      </section>

      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('about.services') }}</h2>
        <div class="mt-4 flex flex-wrap gap-2">
          <span
            v-for="(service, index) in list(aboutPage.services)"
            :key="index"
            class="rounded-full bg-white px-4 py-2 text-sm text-[var(--rdp-forest)] shadow-sm"
          >
            {{ service }}
          </span>
        </div>
      </section>

      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('about.partners') }}</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-3">
          <div
            v-for="(partner, index) in list(aboutPage.partners)"
            :key="index"
            class="rounded-xl border border-[var(--rdp-forest)]/10 bg-white p-4"
          >
            <p class="font-semibold">{{ partner.name }}</p>
            <p class="mt-1 text-sm text-slate-600">{{ partner.desc }}</p>
          </div>
        </div>
      </section>

      <section class="rounded-2xl bg-[var(--rdp-forest)] p-6 text-white">
        <h2 class="text-2xl font-semibold">{{ t('about.local') }}</h2>
        <p class="mt-3 max-w-4xl text-sm leading-relaxed text-white/85">
          {{ localized(aboutPage.localCommunity) }}
        </p>
      </section>

      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('about.why') }}</h2>
        <ul class="mt-4 grid gap-2 sm:grid-cols-2">
          <li
            v-for="(item, index) in list(aboutPage.why)"
            :key="index"
            class="rounded-lg bg-white px-4 py-3 text-sm text-slate-700 shadow-sm"
          >
            {{ item }}
          </li>
        </ul>
      </section>

      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('about.commitments') }}</h2>
        <ul class="mt-4 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
          <li
            v-for="(item, index) in list(aboutPage.commitments)"
            :key="index"
            class="rounded-lg border border-[var(--rdp-forest)]/15 bg-white px-4 py-3 text-sm text-slate-700"
          >
            {{ item }}
          </li>
        </ul>
        <div class="mt-4 flex flex-wrap gap-3 text-sm">
          <RouterLink to="/privacy-policy" class="font-semibold text-[var(--rdp-forest)] hover:underline">
            {{ t('about.privacy') }}
          </RouterLink>
          <span class="text-slate-400">·</span>
          <span class="text-slate-600">{{ t('about.statutesNote') }}</span>
        </div>
      </section>

      <section class="rounded-2xl border border-[var(--rdp-forest)]/15 bg-white p-6">
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('about.contactTitle') }}</h2>
        <p class="mt-2 text-sm text-slate-600">{{ t('about.contactText') }}</p>
        <div class="mt-4 flex flex-wrap gap-3">
          <RouterLink to="/contact" class="rounded bg-[var(--rdp-forest)] px-5 py-2.5 text-sm font-semibold text-white">
            {{ t('nav.contact') }}
          </RouterLink>
          <RouterLink to="/register/member" class="rounded border border-[var(--rdp-forest)] px-5 py-2.5 text-sm font-semibold text-[var(--rdp-forest)]">
            {{ t('home.ctaMember') }}
          </RouterLink>
          <RouterLink to="/register/student" class="rounded border border-[var(--rdp-forest)] px-5 py-2.5 text-sm font-semibold text-[var(--rdp-forest)]">
            {{ t('home.ctaStudent') }}
          </RouterLink>
        </div>
      </section>
    </div>
  </div>
</template>
