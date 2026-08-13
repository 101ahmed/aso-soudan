<script setup>
import { computed, onMounted, ref } from 'vue'
import { RouterLink } from 'vue-router'
import { useI18n } from 'vue-i18n'
import { secretariats } from '@/data/secretariats'
import { fetchPublicDepartments } from '@/services/content'

const { t, locale } = useI18n()
const departments = ref([])

const localized = (value) => value?.[locale.value] || value?.en || value?.fr || value?.ar || ''

const cards = computed(() => {
  const byCode = Object.fromEntries((departments.value || []).map((d) => [d.code, d]))
  return secretariats.map((item) => {
    const dept = byCode[item.slug]
    const officer = dept?.officer
    return {
      ...item,
      officerCard: officer
        ? {
            name: locale.value === 'ar' ? officer.name_ar || officer.name_fr : officer.name_en || officer.name_fr || officer.name_ar,
            title: locale.value === 'ar' ? officer.title_ar || officer.title_fr : officer.title_en || officer.title_fr || officer.title_ar,
            photo: officer.photo_url || item.officer?.photo || null,
          }
        : {
            name: localized(item.officer?.name),
            title: localized(item.officer?.title),
            photo: item.officer?.photo || null,
          },
    }
  })
})

onMounted(async () => {
  try {
    departments.value = await fetchPublicDepartments()
  } catch {
    departments.value = []
  }
})
</script>

<template>
  <div>
    <section class="relative overflow-hidden bg-[var(--rdp-forest)] px-5 py-16 text-white md:px-8">
      <div class="mx-auto max-w-6xl">
        <h1 class="font-[family-name:var(--font-display)] text-4xl font-bold md:text-5xl">
          {{ t('nav.secretariats') }}
        </h1>
        <p class="mt-4 max-w-3xl text-white/85">
          {{ t('pages.secretariats.subtitle') }}
        </p>
      </div>
    </section>

    <section class="mx-auto grid max-w-6xl gap-5 px-5 py-12 md:grid-cols-2 lg:grid-cols-3 md:px-8">
      <article
        v-for="item in cards"
        :key="item.slug"
        class="flex flex-col overflow-hidden rounded-2xl border border-[var(--rdp-forest)]/10 bg-white shadow-sm"
      >
        <img :src="item.banner" alt="" class="h-40 w-full object-cover" />
        <div class="flex flex-1 flex-col p-5">
          <h2 class="text-xl font-semibold text-[var(--rdp-forest)]">
            {{ t(item.nameKey) }}
          </h2>
          <p class="mt-2 text-sm leading-relaxed text-slate-600">
            {{ localized(item.summary) }}
          </p>

          <div v-if="item.officerCard?.name" class="mt-4 flex items-center gap-3 rounded-xl bg-slate-50 p-3">
            <img
              v-if="item.officerCard.photo"
              :src="item.officerCard.photo"
              alt=""
              class="h-12 w-12 rounded-full object-cover object-top"
            />
            <div
              v-else
              class="flex h-12 w-12 items-center justify-center rounded-full bg-[var(--rdp-forest)] text-sm font-bold text-white"
            >
              {{ item.officerCard.name.slice(0, 1) }}
            </div>
            <div class="min-w-0">
              <p class="truncate text-sm font-semibold text-[var(--rdp-ink)]">{{ item.officerCard.name }}</p>
              <p class="truncate text-xs text-[var(--rdp-forest)]">{{ item.officerCard.title }}</p>
            </div>
          </div>

          <RouterLink
            :to="`/secretariats/${item.slug}`"
            class="mt-4 inline-flex text-sm font-semibold text-[var(--rdp-forest)] hover:underline"
          >
            {{ t('pages.secretariats.learnMore') }}
          </RouterLink>
        </div>
      </article>
    </section>
  </div>
</template>
