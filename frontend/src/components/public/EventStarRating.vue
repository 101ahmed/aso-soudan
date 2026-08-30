<script setup>
import { computed, ref, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import { ratePublicEvent, rememberEventRating, storedEventRating } from '@/services/content'

const props = defineProps({
  slug: { type: String, required: true },
  eventId: { type: [Number, String], required: true },
  average: { type: Number, default: 0 },
  count: { type: Number, default: 0 },
  interactive: { type: Boolean, default: true },
})

const emit = defineEmits(['rated'])
const { t } = useI18n()
const hover = ref(0)
const saving = ref(false)
const error = ref('')
const myRating = ref(storedEventRating(props.eventId))
const average = ref(Number(props.average) || 0)
const count = ref(Number(props.count) || 0)

watch(
  () => [props.eventId, props.average, props.count],
  () => {
    myRating.value = storedEventRating(props.eventId)
    average.value = Number(props.average) || 0
    count.value = Number(props.count) || 0
  },
)

const displayStars = computed(() => {
  if (hover.value) return hover.value
  if (myRating.value) return myRating.value
  return Math.round(average.value)
})

const summary = computed(() => {
  if (!count.value) return t('secretariat.rateHint')
  return t('secretariat.ratingSummary', {
    avg: average.value.toFixed(1),
    count: count.value,
  })
})

async function vote(stars) {
  if (!props.interactive || saving.value) return
  saving.value = true
  error.value = ''
  try {
    const updated = await ratePublicEvent(props.slug, stars)
    myRating.value = stars
    rememberEventRating(props.eventId, stars)
    average.value = Number(updated.rating_avg) || stars
    count.value = Number(updated.rating_count) || count.value
    emit('rated', updated)
  } catch (e) {
    error.value = e.response?.data?.message || e.message
  } finally {
    saving.value = false
  }
}
</script>

<template>
  <div class="space-y-1">
    <div class="flex items-center gap-2" dir="ltr">
      <div class="flex" @mouseleave="hover = 0">
        <button
          v-for="n in 5"
          :key="n"
          type="button"
          class="p-0.5 text-xl leading-none transition"
          :class="[
            n <= displayStars ? 'text-[var(--rdp-gold)]' : 'text-slate-300',
            interactive ? 'hover:scale-110' : 'cursor-default',
          ]"
          :disabled="!interactive || saving"
          :aria-label="t('secretariat.rateStars', { n })"
          @mouseenter="interactive && (hover = n)"
          @click="vote(n)"
        >
          ★
        </button>
      </div>
      <p class="text-xs text-slate-500">
        <span v-if="myRating">{{ t('secretariat.yourRating', { n: myRating }) }}</span>
        <span v-else>{{ summary }}</span>
      </p>
    </div>
    <p v-if="myRating && count" class="text-xs text-slate-400">{{ summary }}</p>
    <p v-if="error" class="text-xs text-rose-700">{{ error }}</p>
  </div>
</template>
