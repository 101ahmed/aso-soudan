<script setup>
import { reactive, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { parentsCouncil } from '@/data/parentsCouncil'

const { t, locale } = useI18n()
const contactSent = ref(false)
const proposalSent = ref(false)

const contactForm = reactive({
  name: '',
  email: '',
  subject: '',
  message: '',
})

const proposalForm = reactive({
  parent_name: '',
  email: '',
  title: '',
  type: '',
  details: '',
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
    <section class="relative min-h-[48vh] overflow-hidden">
      <img :src="parentsCouncil.banner" alt="" class="absolute inset-0 h-full w-full object-cover" />
      <div class="absolute inset-0 bg-[linear-gradient(115deg,rgba(18,40,28,0.92),rgba(18,40,28,0.55))]" />
      <div class="relative z-10 mx-auto flex min-h-[48vh] max-w-6xl flex-col justify-end gap-4 px-5 py-12 md:px-8">
        <img
          src="/logo.png"
          :alt="t('app.name')"
          class="h-16 w-auto self-start rounded-md bg-white object-contain px-2 py-1 shadow"
        />
        <h1 class="font-[family-name:var(--font-display)] text-4xl font-bold text-white md:text-5xl">
          {{ t('org.parents') }}
        </h1>
        <p class="max-w-3xl text-lg text-[var(--rdp-gold)]">
          {{ localized(parentsCouncil.tagline) }}
        </p>
        <p class="max-w-3xl text-base text-white/85">
          {{ localized(parentsCouncil.subtitle) }}
        </p>
      </div>
    </section>

    <div class="mx-auto max-w-6xl space-y-14 px-5 py-12 md:px-8">
      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('parents.about') }}</h2>
        <p class="mt-3 max-w-4xl leading-relaxed text-slate-700">
          {{ localized(parentsCouncil.intro) }}
        </p>
      </section>

      <section class="grid gap-4 md:grid-cols-2">
        <div class="rounded-2xl bg-white p-6 shadow-sm">
          <h3 class="text-xl font-semibold text-[var(--rdp-forest)]">{{ t('parents.vision') }}</h3>
          <p class="mt-3 text-sm leading-relaxed text-slate-700">{{ localized(parentsCouncil.vision) }}</p>
        </div>
        <div class="rounded-2xl bg-white p-6 shadow-sm">
          <h3 class="text-xl font-semibold text-[var(--rdp-forest)]">{{ t('parents.mission') }}</h3>
          <p class="mt-3 text-sm leading-relaxed text-slate-700">{{ localized(parentsCouncil.mission) }}</p>
        </div>
      </section>

      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('parents.objectives') }}</h2>
        <ul class="mt-4 grid gap-2 sm:grid-cols-2">
          <li
            v-for="(item, index) in list(parentsCouncil.objectives)"
            :key="index"
            class="rounded-lg border border-[var(--rdp-forest)]/10 bg-white px-4 py-3 text-sm text-slate-700"
          >
            {{ item }}
          </li>
        </ul>
      </section>

      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('parents.competencies') }}</h2>
        <ul class="mt-4 grid gap-2 sm:grid-cols-2">
          <li
            v-for="(item, index) in list(parentsCouncil.competencies)"
            :key="index"
            class="rounded-lg bg-[var(--rdp-forest)]/5 px-4 py-3 text-sm text-slate-700"
          >
            {{ item }}
          </li>
        </ul>
      </section>

      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('parents.structure') }}</h2>
        <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
          <div
            v-for="(item, index) in list(parentsCouncil.structure)"
            :key="index"
            class="rounded-xl border border-[var(--rdp-forest)]/15 bg-white p-4"
          >
            <p class="font-semibold text-[var(--rdp-forest)]">{{ item.role }}</p>
            <p class="mt-2 text-sm text-slate-600">{{ item.note }}</p>
          </div>
        </div>
      </section>

      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('parents.members') }}</h2>
        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
          <article
            v-for="(member, index) in parentsCouncil.members"
            :key="index"
            class="rounded-2xl bg-white p-5 shadow-sm"
          >
            <div class="mb-3 flex h-14 w-14 items-center justify-center rounded-full bg-[var(--rdp-forest)] text-lg font-bold text-white">
              {{ localized(member.name).slice(0, 1) }}
            </div>
            <p class="text-sm font-semibold text-[var(--rdp-forest)]">{{ localized(member.role) }}</p>
            <p class="mt-1 font-medium">{{ localized(member.name) }}</p>
            <p class="mt-2 text-sm text-slate-600">{{ localized(member.bio) }}</p>
          </article>
        </div>
      </section>

      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('parents.meetings') }}</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
          <article
            v-for="meeting in parentsCouncil.meetings"
            :key="meeting.slug"
            class="rounded-2xl border border-[var(--rdp-forest)]/10 bg-white p-5"
          >
            <div class="flex flex-wrap items-center justify-between gap-2">
              <h3 class="font-semibold">{{ localized(meeting.title) }}</h3>
              <span class="rounded-full bg-[var(--rdp-forest)]/10 px-3 py-1 text-xs font-medium text-[var(--rdp-forest)]">
                {{ localized(meeting.status) }}
              </span>
            </div>
            <p class="mt-2 text-sm text-slate-500">
              {{ meeting.date }} · {{ meeting.time }} · {{ localized(meeting.place) }}
            </p>
            <p class="mt-3 text-sm text-slate-700">{{ localized(meeting.summary) }}</p>
            <p class="mt-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
              {{ t('parents.meetingTopics') }}
            </p>
            <ul class="mt-2 list-disc space-y-1 pe-5 text-sm text-slate-600">
              <li v-for="(topic, index) in list(meeting.topics)" :key="index">{{ topic }}</li>
            </ul>
          </article>
        </div>
      </section>

      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('parents.activities') }}</h2>
        <div class="mt-4 flex flex-wrap gap-2">
          <span
            v-for="(activity, index) in list(parentsCouncil.activities)"
            :key="index"
            class="rounded-full bg-white px-4 py-2 text-sm text-[var(--rdp-forest)] shadow-sm"
          >
            {{ activity }}
          </span>
        </div>
      </section>

      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('parents.events') }}</h2>
        <div class="mt-4 grid gap-4 md:grid-cols-2">
          <article
            v-for="event in parentsCouncil.events"
            :key="event.slug"
            class="overflow-hidden rounded-xl bg-white shadow-sm"
          >
            <img :src="event.image" alt="" class="h-40 w-full object-cover" />
            <div class="space-y-2 p-4">
              <div class="flex items-center justify-between gap-2">
                <h3 class="font-semibold">{{ localized(event.title) }}</h3>
                <span class="text-xs text-[var(--rdp-forest)]">{{ localized(event.status) }}</span>
              </div>
              <p class="text-sm text-slate-500">
                {{ event.date }} · {{ event.time }} · {{ localized(event.place) }}
              </p>
              <p class="text-sm text-slate-500">{{ t('parents.audience') }}: {{ localized(event.audience) }}</p>
              <p class="text-sm text-slate-600">{{ localized(event.summary) }}</p>
              <RouterLink to="/events" class="text-sm font-semibold text-[var(--rdp-forest)] hover:underline">
                {{ t('home.eventDetails') }}
              </RouterLink>
            </div>
          </article>
        </div>
      </section>

      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('parents.announcements') }}</h2>
        <div class="mt-4 space-y-3">
          <article
            v-for="(item, index) in parentsCouncil.announcements"
            :key="index"
            class="rounded-xl bg-white p-5 shadow-sm"
          >
            <p class="text-xs text-slate-500">{{ item.date }} · Public</p>
            <h3 class="mt-1 font-semibold">{{ localized(item.title) }}</h3>
            <p class="mt-2 text-sm text-slate-600">{{ localized(item.summary) }}</p>
          </article>
        </div>
      </section>

      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('parents.publicStats') }}</h2>
        <div class="mt-4 grid grid-cols-2 gap-3 md:grid-cols-4">
          <div
            v-for="stat in parentsCouncil.stats"
            :key="stat.key"
            class="rounded-xl bg-white px-4 py-5 text-center shadow-sm"
          >
            <p class="text-2xl font-bold text-[var(--rdp-forest)]">{{ stat.value }}</p>
            <p class="mt-1 text-sm text-slate-600">{{ t(`parents.statLabels.${stat.key}`) }}</p>
          </div>
        </div>
      </section>

      <section id="proposals" class="rounded-2xl border border-[var(--rdp-forest)]/15 bg-white p-6">
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('parents.proposals') }}</h2>
        <p class="mt-2 text-sm text-slate-600">{{ t('parents.proposalsHint') }}</p>
        <p class="mt-2 text-sm text-amber-800">{{ localized(parentsCouncil.privacyNote) }}</p>

        <p v-if="proposalSent" class="mt-4 text-sm text-teal-800">{{ t('parents.proposalSuccess') }}</p>
        <form v-else class="mt-4 grid gap-3 md:grid-cols-2" @submit.prevent="submitProposal">
          <input v-model="proposalForm.parent_name" required :placeholder="t('parents.parentName')" class="rounded border border-slate-300 px-3 py-2" />
          <input v-model="proposalForm.email" required type="email" :placeholder="t('forms.email')" class="rounded border border-slate-300 px-3 py-2" />
          <input v-model="proposalForm.title" required :placeholder="t('parents.proposalTitle')" class="rounded border border-slate-300 px-3 py-2 md:col-span-2" />
          <select v-model="proposalForm.type" required class="rounded border border-slate-300 px-3 py-2 md:col-span-2">
            <option disabled value="">{{ t('parents.proposalType') }}</option>
            <option v-for="type in list(parentsCouncil.proposalTypes)" :key="type" :value="type">
              {{ type }}
            </option>
          </select>
          <textarea v-model="proposalForm.details" required rows="4" :placeholder="t('parents.proposalDetails')" class="rounded border border-slate-300 px-3 py-2 md:col-span-2" />
          <button type="submit" class="rounded bg-[var(--rdp-forest)] px-5 py-2.5 text-sm font-semibold text-white md:col-span-2 md:w-fit">
            {{ t('parents.sendProposal') }}
          </button>
        </form>
      </section>

      <section>
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('parents.documents') }}</h2>
        <ul class="mt-4 space-y-2">
          <li
            v-for="(doc, index) in parentsCouncil.documents"
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
      </section>

      <section class="rounded-2xl bg-[var(--rdp-forest)] p-6 text-white">
        <h2 class="text-2xl font-semibold">{{ t('parents.academicLink') }}</h2>
        <p class="mt-2 max-w-3xl text-sm text-white/85">{{ t('parents.academicLinkText') }}</p>
        <div class="mt-4 flex flex-wrap gap-3">
          <RouterLink
            to="/secretariats/academic"
            class="rounded bg-[var(--rdp-gold)] px-5 py-2.5 text-sm font-semibold text-[var(--rdp-ink)]"
          >
            {{ t('org.academic') }}
          </RouterLink>
          <RouterLink
            to="/register/student"
            class="rounded border border-white/70 px-5 py-2.5 text-sm font-semibold text-white"
          >
            {{ t('home.ctaStudent') }}
          </RouterLink>
        </div>
      </section>

      <section id="contact" class="rounded-2xl border border-[var(--rdp-forest)]/15 bg-white p-6">
        <h2 class="text-2xl font-semibold text-[var(--rdp-forest)]">{{ t('parents.contact') }}</h2>
        <p class="mt-2 text-sm text-slate-600">{{ t('parents.contactHint') }}</p>
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
    </div>
  </div>
</template>
