<script setup>
import { reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { useAuthStore } from '@/stores/auth'
import { shuraCouncil } from '@/data/shuraCouncil'

const { t, locale } = useI18n()
const auth = useAuthStore()
const contactSent = ref(false)
const proposalSent = ref(false)

const contactForm = reactive({
  name: '',
  email: '',
  subject: '',
  message: '',
})

const proposalForm = reactive({
  title: '',
  description: '',
  submitter: '',
})

const localized = (value) => value?.[locale.value] || value?.fr || value?.ar || ''
const list = (value) => {
  const items = value?.[locale.value] || value?.fr || value?.ar || []
  return Array.isArray(items) ? items : []
}

function submitContact() {
  contactSent.value = true
}

function submitProposal() {
  proposalSent.value = true
}
</script>

<template>
  <div>
    <!-- Banner -->
    <section class="relative min-h-[48vh] overflow-hidden">
      <img :src="shuraCouncil.banner" alt="" class="absolute inset-0 h-full w-full object-cover" />
      <div class="absolute inset-0 bg-[linear-gradient(115deg,rgba(18,40,28,0.92),rgba(18,40,28,0.55))]" />
      <div class="relative z-10 mx-auto flex min-h-[48vh] max-w-6xl flex-col justify-end gap-4 px-5 py-12 md:px-8">
        <img
          src="/logo.png"
          :alt="t('app.name')"
          class="h-16 w-auto self-start rounded-md bg-white object-contain px-2 py-1 shadow"
        />
        <h1 class="font-[family-name:var(--font-display)] text-4xl font-bold text-white md:text-5xl">
          {{ t('org.shura') }}
        </h1>
        <p class="max-w-3xl text-lg text-[var(--rdp-gold)]">
          {{ localized(shuraCouncil.tagline) }}
        </p>
        <p class="max-w-3xl text-base text-white/85">
          {{ localized(shuraCouncil.subtitle) }}
        </p>
      </div>
    </section>

    <div class="mx-auto max-w-6xl space-y-14 px-5 py-12 md:px-8">
      <!-- About -->
      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('shura.about') }}</h2>
        <p class="mt-3 max-w-4xl leading-relaxed text-slate-700">
          {{ localized(shuraCouncil.intro) }}
        </p>
      </section>

      <!-- Vision / Mission -->
      <section class="grid gap-4 md:grid-cols-2">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
          <h3 class="text-xl font-semibold text-[var(--rdp-forest)]">{{ t('shura.vision') }}</h3>
          <p class="mt-3 text-sm leading-relaxed text-slate-700">{{ localized(shuraCouncil.vision) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-6 shadow-sm">
          <h3 class="text-xl font-semibold text-[var(--rdp-forest)]">{{ t('shura.mission') }}</h3>
          <p class="mt-3 text-sm leading-relaxed text-slate-700">{{ localized(shuraCouncil.mission) }}</p>
        </div>
      </section>

      <!-- Objectives -->
      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('shura.objectives') }}</h2>
        <ul class="mt-4 grid gap-2 sm:grid-cols-2">
          <li
            v-for="(item, index) in list(shuraCouncil.objectives)"
            :key="index"
            class="rounded-lg border border-[var(--rdp-forest)]/10 bg-white px-4 py-3 text-sm text-slate-700"
          >
            {{ item }}
          </li>
        </ul>
      </section>

      <!-- Competencies -->
      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('shura.competencies') }}</h2>
        <ul class="mt-4 grid gap-2 sm:grid-cols-2">
          <li
            v-for="(item, index) in list(shuraCouncil.competencies)"
            :key="index"
            class="rounded-lg bg-[var(--rdp-forest)]/5 px-4 py-3 text-sm text-slate-700"
          >
            {{ item }}
          </li>
        </ul>
      </section>

      <!-- Structure -->
      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('shura.structure') }}</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <div
            v-for="(item, index) in list(shuraCouncil.structure)"
            :key="index"
            class="rounded-xl border border-[var(--rdp-forest)]/15 bg-white p-4"
          >
            <p class="font-semibold text-[var(--rdp-forest)]">{{ item.role }}</p>
            <p class="mt-2 text-sm text-slate-600">{{ item.note }}</p>
          </div>
        </div>
      </section>

      <!-- Members -->
      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('shura.members') }}</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <article
            v-for="(member, index) in shuraCouncil.members"
            :key="index"
            class="rounded-2xl bg-white p-5 shadow-sm"
          >
            <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-[var(--rdp-forest)] text-lg font-bold text-white">
              {{ localized(member.name).slice(0, 1) }}
            </div>
            <p class="text-sm font-semibold text-[var(--rdp-forest)]">{{ localized(member.role) }}</p>
            <p class="mt-1 font-medium text-[var(--rdp-ink)]">{{ localized(member.name) }}</p>
            <p class="mt-1 text-xs text-slate-500">{{ localized(member.term) }}</p>
            <p class="mt-2 text-sm text-slate-600">{{ localized(member.bio) }}</p>
          </article>
        </div>
      </section>

      <!-- Meetings -->
      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('shura.meetings') }}</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
          <article
            v-for="meeting in shuraCouncil.meetings"
            :key="meeting.slug"
            class="rounded-2xl border border-[var(--rdp-forest)]/10 bg-white p-5"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <h3 class="font-semibold text-[var(--rdp-ink)]">{{ localized(meeting.title) }}</h3>
              <span class="rounded-full bg-[var(--rdp-forest)]/10 px-3 py-1 text-xs font-medium text-[var(--rdp-forest)]">
                {{ localized(meeting.status) }}
              </span>
            </div>
            <p class="mt-2 text-sm text-slate-500">
              {{ meeting.date }} · {{ meeting.time }} · {{ localized(meeting.place) }}
            </p>
            <p class="mt-1 text-sm text-slate-500">{{ t('shura.meetingType') }}: {{ localized(meeting.type) }}</p>
            <p class="mt-3 text-sm text-slate-700">{{ localized(meeting.summary) }}</p>
            <ul class="mt-3 list-disc space-y-1 pe-5 text-sm text-slate-600">
              <li v-for="(topic, index) in list(meeting.topics)" :key="index">{{ topic }}</li>
            </ul>
          </article>
        </div>
      </section>

      <!-- Recommendations -->
      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('shura.recommendations') }}</h2>
        <div class="mt-4 space-y-3">
          <article
            v-for="item in shuraCouncil.recommendations"
            :key="item.ref"
            class="rounded-xl bg-white p-5 shadow-sm"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <p class="text-xs font-medium text-slate-500">{{ item.ref }} · {{ item.date }}</p>
              <span class="rounded-full bg-slate-100 px-3 py-1 text-xs text-slate-700">
                {{ localized(item.status) }}
              </span>
            </div>
            <h3 class="mt-2 font-semibold">{{ localized(item.title) }}</h3>
            <p class="mt-2 text-sm text-slate-600">{{ localized(item.summary) }}</p>
          </article>
        </div>
      </section>

      <!-- News -->
      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('shura.news') }}</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
          <article
            v-for="item in shuraCouncil.news"
            :key="item.slug"
            class="overflow-hidden rounded-xl bg-white shadow-sm"
          >
            <img :src="item.image" alt="" class="h-40 w-full object-cover" />
            <div class="space-y-2 p-4">
              <p class="text-xs text-slate-500">{{ item.date }}</p>
              <h3 class="font-semibold">{{ localized(item.title) }}</h3>
              <p class="text-sm text-slate-600">{{ localized(item.excerpt) }}</p>
            </div>
          </article>
        </div>
      </section>

      <!-- Events -->
      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('shura.events') }}</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
          <article
            v-for="event in shuraCouncil.events"
            :key="event.slug"
            class="overflow-hidden rounded-xl bg-white shadow-sm"
          >
            <img :src="event.image" alt="" class="h-40 w-full object-cover" />
            <div class="space-y-2 p-4">
              <div class="flex items-center justify-between gap-2">
                <h3 class="font-semibold">{{ localized(event.title) }}</h3>
                <span class="text-xs text-[var(--rdp-forest)]">{{ localized(event.status) }}</span>
              </div>
              <p class="text-sm text-slate-500">{{ event.date }} · {{ localized(event.place) }}</p>
              <p class="text-sm text-slate-600">{{ localized(event.summary) }}</p>
            </div>
          </article>
        </div>
      </section>

      <!-- Public documents -->
      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('shura.documents') }}</h2>
        <ul class="mt-4 space-y-2">
          <li
            v-for="(doc, index) in shuraCouncil.documents"
            :key="index"
            class="flex flex-wrap items-center justify-between gap-3 rounded-lg bg-white px-4 py-3 text-sm shadow-sm"
          >
            <div>
              <p class="font-medium">{{ localized(doc.title) }}</p>
              <p class="text-slate-500">{{ localized(doc.type) }} · Public</p>
            </div>
            <span class="rounded border border-[var(--rdp-forest)]/20 px-3 py-1 text-xs text-[var(--rdp-forest)]">
              PDF
            </span>
          </li>
        </ul>
        <p class="mt-3 text-sm text-slate-500">{{ localized(shuraCouncil.privacyNote) }}</p>
      </section>

      <!-- Contact -->
      <section id="contact" class="rounded-2xl border border-[var(--rdp-forest)]/15 bg-white p-6">
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('shura.contact') }}</h2>
        <p class="mt-2 text-sm text-slate-600">{{ t('shura.contactHint') }}</p>
        <p v-if="contactSent" class="mt-4 text-sm text-teal-800">{{ t('pages.contact.success') }}</p>
        <form v-else class="mt-4 grid gap-3 md:grid-cols-2" @submit.prevent="submitContact">
          <input v-model="contactForm.name" required :placeholder="t('forms.name')" class="rounded border border-slate-300 px-3 py-2" />
          <input v-model="contactForm.email" required type="email" :placeholder="t('forms.email')" class="rounded border border-slate-300 px-3 py-2" />
          <input v-model="contactForm.subject" required :placeholder="t('forms.subject')" class="rounded border border-slate-300 px-3 py-2 md:col-span-2" />
          <textarea v-model="contactForm.message" required rows="4" :placeholder="t('forms.message')" class="rounded border border-slate-300 px-3 py-2 md:col-span-2" />
          <button type="submit" class="rounded bg-[var(--rdp-forest)] px-5 py-2.5 text-sm font-semibold text-white md:col-span-2 md:w-fit">
            {{ t('forms.send') }}
          </button>
        </form>
      </section>

      <!-- Proposal (members / logged-in preferred) -->
      <section id="proposal" class="rounded-2xl bg-[var(--rdp-forest)] p-6 text-white">
        <h2 class="text-2xl font-semibold">{{ t('shura.proposal') }}</h2>
        <p class="mt-2 text-sm text-white/80">{{ t('shura.proposalHint') }}</p>

        <div v-if="!auth.isAuthenticated" class="mt-4">
          <p class="text-sm text-white/85">{{ t('shura.proposalLoginRequired') }}</p>
          <RouterLink
            to="/login?redirect=/shura-council#proposal"
            class="mt-3 inline-flex rounded bg-[var(--rdp-gold)] px-5 py-2.5 text-sm font-semibold text-[var(--rdp-ink)]"
          >
            {{ t('nav.login') }}
          </RouterLink>
        </div>

        <div v-else>
          <p v-if="proposalSent" class="mt-4 text-sm text-[var(--rdp-gold)]">{{ t('shura.proposalSuccess') }}</p>
          <form v-else class="mt-4 grid gap-3" @submit.prevent="submitProposal">
            <input v-model="proposalForm.title" required :placeholder="t('shura.proposalTitle')" class="rounded border-0 px-3 py-2 text-[var(--rdp-ink)]" />
            <input v-model="proposalForm.submitter" required :placeholder="t('shura.proposalSubmitter')" class="rounded border-0 px-3 py-2 text-[var(--rdp-ink)]" />
            <textarea v-model="proposalForm.description" required rows="4" :placeholder="t('shura.proposalDescription')" class="rounded border-0 px-3 py-2 text-[var(--rdp-ink)]" />
            <button type="submit" class="w-fit rounded bg-[var(--rdp-gold)] px-5 py-2.5 text-sm font-semibold text-[var(--rdp-ink)]">
              {{ t('forms.send') }}
            </button>
          </form>
        </div>
      </section>
    </div>
  </div>
</template>
