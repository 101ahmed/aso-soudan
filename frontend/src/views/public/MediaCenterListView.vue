<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import { RouterLink, useRoute, useRouter } from 'vue-router'
import { useI18n } from 'vue-i18n'
import PageHero from '@/components/public/PageHero.vue'
import { pickContent, pickTitle } from '@/utils/localized'
import { PRESS_KINDS, fetchPublicMediaCenter } from '@/services/mediaCenter'

const KIND_ICONS = {
  official: '📄',
  statement: '🎙️',
  coverage: '📰',
  conference: '🎤',
  interview: '💬',
}

const route = useRoute()
const router = useRouter()
const { t, locale } = useI18n()
const items = ref([])
const loading = ref(false)

const activeKind = computed(() => (PRESS_KINDS.includes(route.query.kind) ? route.query.kind : ''))

function excerpt(item) {
  return (pickContent(item, locale.value) || '').slice(0, 160)
}

function sourceOf(item) {
  return locale.value === 'ar' ? item.source_ar || item.source_fr : item.source_fr || item.source_ar
}

async function load() {
  loading.value = true
  try {
    const data = await fetchPublicMediaCenter({
      per_page: 24,
      kind: activeKind.value || undefined,
    })
    items.value = data.data || []
  } catch {
    items.value = []
  } finally {
    loading.value = false
  }
}

function setKind(kind) {
  const next = kind && kind !== activeKind.value ? kind : undefined
  router.replace({ path: '/media-center', query: next ? { kind: next } : {} })
}

watch(() => route.query.kind, load)
onMounted(load)
</script>

<template>
  <div>
    <PageHero :title="t('mediaCenter.publicTitle')" :subtitle="t('mediaCenter.publicSubtitle')" />
    <section class="mx-auto max-w-6xl space-y-8 px-5 py-12 md:px-8">
      <div class="flex flex-wrap gap-2">
        <button
          type="button"
          class="rounded-full px-4 py-2 text-sm"
          :class="!activeKind ? 'bg-[var(--rdp-forest)] text-white' : 'bg-white text-slate-700'"
          @click="setKind('')"
        >
          {{ t('mediaCenter.allKinds') }}
        </button>
        <button
          v-for="kind in PRESS_KINDS"
          :key="kind"
          type="button"
          class="rounded-full px-4 py-2 text-sm"
          :class="activeKind === kind ? 'bg-[var(--rdp-forest)] text-white' : 'bg-white text-slate-700'"
          @click="setKind(kind)"
        >
          {{ KIND_ICONS[kind] }} {{ t(`mediaCenter.kinds.${kind}`) }}
        </button>
      </div>

      <p v-if="loading" class="text-sm text-slate-500">{{ t('admin.loading') }}</p>
      <p v-else-if="!items.length" class="text-sm text-slate-500">{{ t('mediaCenter.publicEmpty') }}</p>

      <div class="grid gap-6 md:grid-cols-3">
        <article v-for="item in items" :key="item.id" class="overflow-hidden rounded-xl bg-white shadow-sm">
          <img :src="item.image_url || '/logo.png'" alt="" class="h-44 w-full object-cover" />
          <div class="space-y-2 p-5">
            <p class="text-xs font-semibold text-[var(--rdp-gold)]">
              {{ KIND_ICONS[item.kind] }} {{ t(`mediaCenter.kinds.${item.kind}`) }}
            </p>
            <p class="text-xs text-slate-500">{{ item.occurred_on || (item.published_at || '').slice(0, 10) }}</p>
            <h2 class="text-lg font-semibold">{{ pickTitle(item, locale) }}</h2>
            <p v-if="sourceOf(item)" class="text-sm text-slate-600">{{ sourceOf(item) }}</p>
            <p v-if="excerpt(item)" class="text-sm text-slate-600">{{ excerpt(item) }}</p>
            <RouterLink :to="`/media-center/${item.slug}`" class="text-sm font-semibold text-[var(--rdp-forest)] hover:underline">
              {{ t('home.readMore') }}
            </RouterLink>
          </div>
        </article>
      </div>
    </section>
  </div>
</template>
